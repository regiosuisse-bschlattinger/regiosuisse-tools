# %INSTANCE_ID%Forms

## Setup

### Import CSS (optional)

```html
<link rel="stylesheet" type="text/css" href="%HOST%/embed/forms-latest/embed-forms-latest.css">
```

### Import JavaScript

```html
<script src="%HOST%/embed/forms-latest/embed-forms-latest.js"></script>
```

## Basic Usage

```html
<div id="%INSTANCE_ID%-forms"></div>
```

```javascript
%INSTANCE_ID%Forms('#%INSTANCE_ID%-forms', {
    // options
});
```

## Customization

### Options

| Option              | Type      | Description                                                                              | Example                                                        |
|---------------------|-----------|------------------------------------------------------------------------------------------|----------------------------------------------------------------|
| `locale`            | `String`  | Set the locale.                                                                          | `"de"`                                                         |
| `fixedFilters`      | `Array`   | Set fixed filter options. These aren't visible to the user.                              | `[{ type: "formEntry", entity: { id: 2 } }]`                   |
| `defaultFilters`    | `Array`   | Preselect specific filter options.                                                       | `[{ type: "formEntry", entity: { id: 1 } }]`                   |
| `responsive`        | `Boolean` | Whether responsive media queries should be enabled.                                      | `true`                                                         |
| `middleware`        | `Object`  | (Experimental) Middleware options allow you to define custom methods to manipulate data. | `{ filterFormEntries: formEntry => formEntry.name !== "Foo" }` |
| `disableTelemetry`  | `Boolean` | Disable collection of telemetry data.                                                    | `true`                                                         |
| `history`           | `Object`  | Enable browser history support.                                                          | `{ mode: 'hash', base: 'http://localhost/forms' }`             |

### Styling

Basic styles can be overwritten using CSS variables:

```css
.embed-forms {
    --%INSTANCE_ID%-max-width: none;
    --%INSTANCE_ID%-gutter-width: 1em;
    --%INSTANCE_ID%-primary-color: #0059be;
    --%INSTANCE_ID%-secondary-color: #92bae2;
    --%INSTANCE_ID%-border-radius-1: 0;
    --%INSTANCE_ID%-border-radius-2: 0;
}
```

> For more available variables have a look at %HOST%/embed/forms-latest/embed-forms-latest.css

## Full Example

```html
<link rel="stylesheet" type="text/css" href="%HOST%/embed/forms-latest/embed-forms-latest.css">

<script src="%HOST%/embed/forms-latest/embed-forms-latest.js"></script>

<div id="%INSTANCE_ID%-forms"></div>

<style>
    .embed-forms {
        --%INSTANCE_ID%-primary-color: #0059be;
        --%INSTANCE_ID%-secondary-color: #92bae2;
    }
</style>

<script>
    %INSTANCE_ID%Forms('#%INSTANCE_ID%-forms', {
        locale: 'fr',
        responsive: true,
        fixedFilters: [
            { 
                type: 'formEntry', 
                entity: { 
                    id: 1,
                } 
            }
        ],
        defaultFilters: [
            { 
                type: 'formEntry', 
                entity: { 
                    id: 1,
                } 
            }
        ],
        middleware: {
            mapFormEntries: formEntry => ({ ...formEntry, name: formEntry.name === 'Foo' ? 'Bar' : formEntry.name }),
            filterFormEntries: formEntry => formEntry.id !== 1,
            sortFormEntries: (a, b) => a.name.localeCompare(b.name),
        },
    });
</script>
```