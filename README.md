
# Tadasei/backend-crud-stubs

This package provides stubs for generating CRUD backends using different technology stacks in a Laravel application. It aims to simplify and streamline the process of creating common CRUD (Create, Read, Update, Delete) operations by providing pre-defined structures.

## Features

- Quickly generate CRUD backends for different stacks.
- Customize and extend generated code to fit your project's needs.
- Improve development efficiency by eliminating repetitive tasks.

## Installation

You can install the package via Composer by running:

```bash
composer require tadasei/backend-crud-stubs
```

## Usage

### Generating CRUD Backend

To generate a CRUD backend for a specific stack, use the following command:

```bash
php artisan crud:generate {name} --stack={stack}
```

Replace `{stack}` with the desired stack (e.g., `vue`, `blade`, `api`) and `{name}` with the name of the CRUD resource.

### Customization

The generated code serves as a starting point. You can customize and extend it according to your project's requirements. Modify the generated controllers, routes, requests and policies as needed.

## Available Stacks

- `vue`: Generates CRUD backend with Inertia stack.
- `blade`: Generates CRUD backend with Blade stack.
- `api`: Generates CRUD backend with API stack.

## Examples

### Generating an Inertia CRUD Backend

To generate a CRUD backend for Inertia, run:

```bash
php artisan crud:generate Post --stack=vue
```

This will generate the necessary files for managing `Post` resources using Inertia.

## Contributing

Contributions are welcome! If you have suggestions, bug reports, or feature requests, please open an issue on the GitHub repository.

## License

This package is open-source software licensed under the [MIT license](LICENSE).