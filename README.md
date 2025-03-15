# Job Board with Advanced Filtering

## Overview
This Laravel-based Job Board application provides an advanced filtering system similar to Airtable. The system supports job listings with dynamic attributes using the Entity-Attribute-Value (EAV) pattern alongside traditional relational database models. The application enables complex filtering on jobs, relationships, and attributes.

## Features
- Job listings with standard fields like title, description, salary, job type, etc.
- Many-to-Many relationships for **Languages, Locations, and Categories**.
- **Entity-Attribute-Value (EAV) implementation** for dynamic job attributes.
- **Advanced filtering API** supporting logical operators, relational filters, and EAV attributes.
- **Efficient query handling** with a dedicated `JobFilterService`.

---

## Installation
### Requirements
- PHP 8.1+
- Laravel 10+
- MySQL
- Composer
- Postman (for API testing)

### Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/imhaji/JobBoard.git
   cd job-board
   ```
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create a `.env` file:
   ```bash
   cp .env.example .env
   ```
4. Configure the `.env` file (database, app key, etc.):
   ```bash
   php artisan key:generate
   ```
5. Run database migrations and seed data:
   ```bash
   php artisan migrate --seed
   ```
6. Start the server:
   ```bash
   php artisan serve
   ```

---

## Database Schema

### Job Model
| Column         | Type        | Description                     |
|--------------|-----------|------------------------------|
| id           | bigint    | Primary key                  |
| title        | string    | Job title                    |
| description  | text      | Job description              |
| company_name | string    | Company name                 |
| salary_min   | decimal   | Minimum salary               |
| salary_max   | decimal   | Maximum salary               |
| is_remote    | boolean   | Remote job flag              |
| job_type     | enum      | Job type (full-time, part-time, etc.) |
| status       | enum      | Job status (draft, published, etc.) |
| published_at | timestamp | Publish date                 |

### Relationships
- `job_language` (Pivot table: `job_id`, `language_id`)
- `job_location` (Pivot table: `job_id`, `location_id`)
- `job_category` (Pivot table: `job_id`, `category_id`)

### Entity-Attribute-Value (EAV)
- **Attributes Table**: Stores attribute metadata (name, type, options).
- **Job Attribute Values Table**: Stores dynamic attribute values per job.

---

## API Endpoints
### Job Listing with Filters
#### `GET /api/jobs`
##### Query Parameters
- **Basic Filtering**
  - `title=developer` (Exact match)
  - `company_name!=Google` (Not equal)
  - `salary_min>=50000` (Comparison)
- **Relationship Filtering**
  - `languages HAS_ANY (PHP,JavaScript)`
  - `locations IS_ANY (New York,Remote)`
- **EAV Filtering**
  - `attribute:years_experience>=3`
- **Logical Operators**
  - `filter=(job_type=full-time AND (languages HAS_ANY (PHP,JavaScript))) AND (locations IS_ANY (New York,Remote))`

### Example Request
```bash
GET /api/jobs?filter=(job_type=full-time AND languages HAS_ANY (PHP,JavaScript))
```

---

## Job Filtering Service
The `JobFilterService` class parses filter parameters and dynamically builds efficient Eloquent queries.
- Uses query scopes and joins for optimization.
- Prevents N+1 queries.
- Handles nested conditions with AND/OR logic.

---

## Testing
### Postman Collection
Import the provided Postman collection (`postman_collection.json`) to test API endpoints.

### Run Feature Tests
```bash
php artisan test
```

---

## Design Decisions & Trade-offs
- **EAV for Dynamic Attributes**: Allows job types to have different sets of attributes but adds query complexity.
- **Indexing Strategy**: Indexed foreign keys and frequently filtered columns (`salary_min`, `published_at`, etc.).
- **Query Optimization**: Used eager loading and database indexing to improve performance on large datasets.

---

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss proposed changes.

---

## License
This project is open-source under the [MIT License](LICENSE).

