# API Request Logger

A WordPress plugin that logs all outgoing API requests made by WordPress. Provides an admin interface to view, search, delete, export, and retrieve logs via REST API.

## Features
- Logs all outgoing API requests from WordPress.
- Displays logs in a sortable, searchable admin table.
- Bulk delete logs from the admin panel.
- Export logs as a CSV file.
- Provides a REST API endpoint to fetch logs in JSON format.

## Installation
1. **Download & Upload**
    - Clone this repository or download it as a ZIP file.
    - Upload it to your WordPress plugins directory (`wp-content/plugins/api-request-logger`).

2. **Activate the Plugin**
    - Go to `WordPress Admin > Plugins`.
    - Activate the **API Request Logger** plugin.

## Usage
### View API Request Logs
- Navigate to `WordPress Admin > Tools > Monitor API Logs`.
- You will see a table listing API request logs with columns:
    - **Endpoint**: The requested URL.
    - **Method**: HTTP method (GET, POST, etc.).
    - **Response**: HTTP status code.
    - **Time**: Timestamp of the request.

### Searching & Sorting
- Use the search box at the top right to filter logs by endpoint.
- Click on column headers to sort logs.

### Deleting Logs
- Select individual or multiple logs.
- Use the **Bulk Actions** dropdown to delete logs.

### Exporting Logs
- Click the **Export CSV** button next to the bulk actions.
- A CSV file containing all logs will be downloaded.

### REST API Endpoint
- Retrieve logs in JSON format:
  ```
  GET /wp-json/api-request-logger/v1/logs
  ```
- Example response:
  ```json
  [
    {
      "id": 1,
      "endpoint": "https://jsonplaceholder.typicode.com/todos/1",
      "method": "GET",
      "response": 200,
      "time": "2025-03-11 14:30:00"
    }
  ]
  ```

## Troubleshooting
- If REST API is not working, try re-saving **Settings > Permalinks** in WordPress.
