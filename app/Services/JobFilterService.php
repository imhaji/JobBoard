<?php
namespace App\Services;

use App\Models\Attribute;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class JobFilterService
{
    private static $fieldTypes = [
        'title' => 'string',
        'description' => 'string',
        'company_name' => 'string',
        'salary_min' => 'decimal',
        'salary_max' => 'decimal',
        'is_remote' => 'boolean',
        'job_type' => 'enum',
        'status' => 'enum',
        'published_at' => 'timestamp',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    /**
     * Apply the filter to the Job query.
     */
    public static function apply($filter)
    {
        $query = Job::query()->with(['languages', 'locations', 'categories', 'attributeValues.attribute']);
        if ($filter) {
            $tokens = self::tokenize($filter);
            $index = 0;
            $tree = self::parseFilter($tokens, $index);
            self::applyFilterNode($query, $tree);
        }
        return $query->get();
    }

    /**
     * Tokenize the filter string into an array of tokens.
     */
    private static function tokenize($filter)
    {
        preg_match_all('/\(|\\)|[^\\s()]+/', $filter, $matches);
        return $matches[0];
    }

    /**
     * Recursively parse tokens into a filter tree.
     */
    private static function parseFilter(array $tokens, &$index)
    {
        $node = new FilterNode();
        while ($index < count($tokens)) {
            $token = $tokens[$index];
            if ($token === ')') {
                break;
            } elseif ($token === '(') {
                $index++;
                $child = self::parseFilter($tokens, $index);
                $node->children[] = $child;
            } elseif ($token === 'AND' || $token === 'OR') {
                $node->type = 'operator';
                $node->value = $token;
                $index++;
                $child = self::parseFilter($tokens, $index);
                $node->children[] = $child;
            } else {
                // Check for relationship condition (multi-token)
                if (
                    isset($tokens[$index + 1]) &&
                    !in_array($tokens[$index + 1], ['(', ')']) &&
                    isset($tokens[$index + 2]) &&
                    $tokens[$index + 2] === '('
                ) {
                    $relation = $token;
                    $operator = $tokens[$index + 1];
                    $index += 3; // Move to value token after '('
                    $valuesToken = $tokens[$index];
                    $values = explode(',', $valuesToken);
                    $index += 2; // Skip value and ')'
                    $condition = ['relation', $relation, $operator, $values];
                    $condNode = new FilterNode();
                    $condNode->type = 'condition';
                    $condNode->value = $condition;
                    $node->children[] = $condNode;
                } else {
                    // Field or attribute condition (single-token)
                    $condition = self::parseCondition($token);
                    $condNode = new FilterNode();
                    $condNode->type = 'condition';
                    $condNode->value = $condition;
                    $node->children[] = $condNode;
                    $index++;
                }
            }
        }
        return $node;
    }

    /**
     * Parse a single-token condition (field or attribute).
     */
    private static function parseCondition($condition)
    {
        if (preg_match('/attribute:(\w+)([<>=!]+)(.*)/', $condition, $matches)) {
            return ['attribute', $matches[1], $matches[2], $matches[3]];
        } else {
            preg_match('/(\w+)([<>=!]+|LIKE)(.*)/', $condition, $matches);
            return ['field', $matches[1], $matches[2], $matches[3]];
        }
    }

    /**
     * Apply the filter node to the query.
     */
    private static function applyFilterNode(Builder $query, FilterNode $node)
    {
        if ($node->type === 'condition') {
            $condition = $node->value;
            $type = $condition[0];
            if ($type === 'field') {
                [, $field, $operator, $value] = $condition;
                if (isset(self::$fieldTypes[$field])) {
                    $fieldType = self::$fieldTypes[$field];
                    if ($fieldType === 'boolean') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    } elseif ($fieldType === 'decimal') {
                        $value = (float)$value;
                    } elseif ($fieldType === 'timestamp') {
                        $value = Carbon::parse($value);
                    }
                }
                $query->where($field, $operator === 'LIKE' ? 'LIKE' : $operator, $operator === 'LIKE' ? "%$value%" : $value);
            } elseif ($type === 'relation') {
                [, $relation, $operator, $values] = $condition;
                if (in_array($operator, ['HAS_ANY', 'IS_ANY'])) {
                    $query->whereHas($relation, function ($q) use ($values, $relation) {
                        $field = in_array($relation, ['languages', 'categories']) ? 'name' : 'city';
                        $q->whereIn($field, $values);
                    });
                }
            } elseif ($type === 'attribute') {
                [, $attributeName, $operator, $value] = $condition;
                $attribute = Attribute::where('name', $attributeName)->first();
                if ($attribute) {
                    $query->whereHas('attributeValues', function ($q) use ($attribute, $operator, $value) {
                        $q->where('attribute_id', $attribute->id);
                        $attrType = $attribute->type;
                        if ($attrType === 'number') {
                            $q->where('value', $operator, (float)$value);
                        } elseif ($attrType === 'boolean') {
                            $q->where('value', $operator, filter_var($value, FILTER_VALIDATE_BOOLEAN));
                        } elseif ($attrType === 'date') {
                            $q->where('value', $operator, Carbon::parse($value));
                        } else {
                            $q->where('value', $operator === 'LIKE' ? 'LIKE' : $operator, $operator === 'LIKE' ? "%$value%" : $value);
                        }
                    });
                }
            }
        } elseif ($node->type === 'operator') {
            if ($node->value === 'AND') {
                foreach ($node->children as $child) {
                    self::applyFilterNode($query, $child);
                }
            } elseif ($node->value === 'OR') {
                $query->where(function ($q) use ($node) {
                    $first = true;
                    foreach ($node->children as $child) {
                        if ($first) {
                            $q->where(function ($q2) use ($child) {
                                self::applyFilterNode($q2, $child);
                            });
                            $first = false;
                        } else {
                            $q->orWhere(function ($q2) use ($child) {
                                self::applyFilterNode($q2, $child);
                            });
                        }
                    }
                });
            }
        }
    }
}

/**
 * Represents a node in the filter tree.
 */
class FilterNode
{
    public $type;
    public $value;
    public $children = [];
}
