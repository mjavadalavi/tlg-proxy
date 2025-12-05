# Telegram API Proxy

A simple PHP-based proxy for the Telegram Bot API. This project allows you to forward requests to the Telegram API through your own server, which can be useful for bypassing restrictions or logging requests.

## Features

-   **Lightweight**: Built with PHP and Apache.
-   **Dockerized**: Easy to deploy using Docker and Docker Compose.
-   **Transparent Proxy**: Forwards GET and POST requests directly to `api.telegram.org`.

## Prerequisites

-   [Docker](https://www.docker.com/get-started)
-   [Docker Compose](https://docs.docker.com/compose/install/)

## Installation & Usage

1.  **Clone the repository** (if applicable) or download the source code.

2.  **Start the container**:
    Run the following command in the project root directory:

    ```bash
    docker-compose up -d --build
    ```

    This will start the service on port `8080`.

3.  **Making Requests**:
    You can now send requests to your proxy instead of the official Telegram API URL.

    **Original URL:**
    `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getMe`

    **Proxy URL:**
    `http://localhost:8080/bot<YOUR_BOT_TOKEN>/getMe`

    **Example:**
    ```bash
    curl http://localhost:8080/bot123456789:ABCdefGHIjklMNOpqrsTUVwxyz/getMe
    ```

## Configuration

-   **Port**: By default, the service runs on port `8080`. You can change this in the `docker-compose.yml` file.
-   **Time Limit**: The script has a default execution time limit of 30 seconds.

## Project Structure

-   `index.php`: The main entry point that handles request forwarding.
-   `.htaccess`: Apache configuration for URL rewriting.
-   `Dockerfile`: Docker image definition (PHP 8.2 + Apache).
-   `docker-compose.yml`: Service orchestration configuration.
