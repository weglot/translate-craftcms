# Contributing to the Weglot Plugin for Craft CMS

This guide aims to define the rules and best practices to follow for any contribution. Adhering to these standards allows us to maintain high-quality, consistent, and easy-to-maintain code.

## Core Principles

- **Clarity Above All**: The code should be as readable and explicit as possible.
- **Respect the Ecosystem**: We work within the Craft CMS environment, so we must respect its standards and specificities, which are largely based on the Yii framework.

## Coding Standards

We follow the official [Craft CMS coding standards](https://craftcms.com/docs/5.x/extend/coding-guidelines.html), which are based on PSR-12. To help us maintain this quality, we use several static analysis tools.

### 1. Static Analysis and Code Quality

Before submitting your code, make sure it passes our tool checks.

- **Easy Coding Standard (ECS)**: We use ECS to enforce a consistent code style. You can run the checks with the following command:
  ```bash
  vendor/bin/ecs check src
  ```
- **PHPStan**: We use it to detect potential errors without having to run the code. The configuration is located in `phpstan.neon`. Run it via:
  ```bash
  vendor/bin/phpstan analyse -c phpstan.neon src --memory-limit=-1
  ```
- **Rector**: We use Rector for automated refactoring and to keep the code modern. You can check for possible upgrades with:
    ```bash
    vendor/bin/rector process src --dry-run
    ```

Even though these tools are here to help, manual vigilance is always required.

### 2. Comments

Comments are essential for maintainability. However, they should explain the **"why,"** not the **"what."** The code itself should be clear enough to explain what it does.

- **Good:** `// We use a direct query for performance reasons where Active Record would be too slow.`
- **Not so good:** `// Get the sites.`

### 3. Naming Conventions

The names of classes, methods, and variables must be clear, explicit, and in English, following the PSR standards.

- **Classes:** `PascalCase`, e.g., `class LanguageService`
- **Methods & Variables:** `camelCase`, e.g., `function getTranslatedEntries()`, `$entryElement`
- **Avoid abbreviations:**
    - **Good:** `class ParserService`, `function getClient()`
    - **Not so good:** `class PrsrSrvc`, `function get_client()`

### 4. Specific Style Rules

To ensure clarity and avoid unexpected behavior, we enforce the following rules:

- **Strict Comparisons**: Always use `===` or `!==` instead of `==` or `!=` to avoid type coercion errors.
- **No `empty()`**: The `empty()` function can be confusing because it returns `true` for many different "empty-like" values (`''`, `0`, `'0'`, `null`, `false`, `[]`). Be explicit about what you are testing.
    - **Prefer:** `if ($variable !== null)`, `if ($count > 0)`, `if ($string !== '')`
    - **Avoid:** `if (!empty($variable))`

### 5. Use Craft APIs

Before using a native PHP function or creating a utility from scratch, always check if a Craft helper or service exists. These are often more secure and better integrated into the ecosystem.

- **Example:** Use `Craft::createGuzzleClient()` for HTTP requests instead of initializing a new Guzzle client.
- **Example:** Use Craft's services like `Craft::$app->getSites()->getSiteByHandle()` instead of writing direct database queries for common tasks.

### 6. Security

All data, whether from a user or the database, must be systematically validated on input and escaped on output.

- **Validation (Input):** Use model validation rules on your setting models or any other model that handles user input.
- **Escaping (Output):** Twig templates in Craft auto-escape output by default, which prevents most XSS vulnerabilities. When generating HTML manually in PHP, use helpers like `craft\helpers\Html::encode()` to escape content. Be extremely careful when using `\craft\helpers\Html::tag()` or `Template::raw()`.

## Unit Tests

Quality is our priority. All new code (services, methods) should be covered by unit tests using PHPUnit. If you modify an existing portion of code that is not tested, please consider adding the corresponding tests in the `/tests` directory.

## Pull Request (PR) Process

1.  **Fork** the project and clone it locally.
2.  Create a **new descriptive branch** for your changes (e.g., `feature/add-new-switcher` or `fix/issue-123`).
3.  Make your changes while respecting the standards described above.
4.  Ensure your code passes all static analysis checks.
5.  Ensure your changes are covered by unit tests where applicable.
6.  Submit your **Pull Request**, clearly explaining the changes you have made and why. Link it to an issue if one exists.