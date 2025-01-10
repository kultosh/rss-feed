## About RSS FEEDS

This Laravel-based project generates **RSS feeds** for specific sections (e.g., `/news`, `/sport`) of The Guardian's content using their Open Platform API. The RSS feed is **W3C-compliant**, optimized for performance, and includes the latest articles with metadata like title, description, publication date, and more. It's designed for developers and applications that need dynamic, real-time news feeds.

## Table Of Contents

1.  [Features](#features)
2.  [Prerequisites](#prerequisites)
3.  [Installation](#installation)
4.  [Configuration](#configuration)
5.  [Usage](#usage)
6.  [API Endpoints](#endpoints)
8.  [Testing W3C Compliance](#testing-w3c-compliance)
9.  [Caching](#caching)
10. [Logging](#logging)
11. [Docker Containerization (Optional)](#docker-containerization)
12. [Technologies Used](#technologies-used)
13. [Contributing](#contributing)

## Features
- Fetches articles from The Guardian API based on section names.
- Converts API responses into **RSS 2.0 feeds**.
- Caches feeds for a configurable duration to optimize performance.
- Includes metadata such as title, link, description, publication date, and thumbnail.
- Validates feeds to ensure compliance with **W3C standards**.

## Prerequisites

Before you begin, ensure you have the following installed on your local machine:

- [PHP](https://www.php.net/) (v8.1 or higher)
- [Composer](https://getcomposer.org/)
- [Laravel](https://laravel.com/) (v11)
- [API Key From The Guardian Open Platform](https://open-platform.theguardian.com/access/)

## Installation

### Clone The Repository:

```bash
git clone https://github.com/kultosh/rss-feed.git
cd rss-feed
```

### Install The Dependencies :

```bash
composer install
```

## Configuration

### .env Setup
1. Add the following variables to your .env file:
```bash
GUARDIAN_URL=https://content.guardianapis.com/
GUARDIAN_API_KEY=your-api-key
GUARDIAN_CACHE_TIME=10
```
2. Generate the application key:
```bash
php artisan key:generate
```

## Usage
- Run the Application:
```bash
php artisan serve
```

## Endpoints

| HTTP Method | Endpoint                         | Description                                 |
|-------------|----------------------------------|---------------------------------------------|
| `GET`       | `/{section}`                     | Fetch the RSS Feed for a specific section   |

Example request:
```bash
http://localhost:8000/news
```
This will return an RSS feed with the latest articles from the "news" section.
<p align="center"><a href="https://share.nmblc.cloud/de384b25" target="_blank"><img src="https://share.nmblc.cloud/1736490920992-screenshot-localhost_8000-2025_01_10-12_19_08.png" width="900" alt="RssFeed"></a></p>

## Testing W3C Compliance
- Use the [W3C Feed Validator](https://validator.w3.org/feed/).
- Expose your local environment using tools like **Ngrok**, or upload your feed as an XML file. Alternatively, copy your RSS feed content from the browser and paste it into the validator.


## Caching
- RSS feeds are cached for a configurable duration (default: 10 minutes).
- You can modify the cache time by updating the GUARDIAN_CACHE_TIME value in your .env file.

## Logging
- The application uses Monolog for logging errors and warnings.
- Logs are stored in storage/logs/laravel.log.

## Docker Containerization (Optional)

If you prefer running the application in a Dockerized environment, follow these steps:

### Prerequisites
- Install [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/).

### Switch to the Docker Branch
Ensure you are on the correct branch for Docker containerization:
```bash
git checkout docker-containerization
```

### Build and Run the Containers
1. Build the Docker Containers:
```bash
docker-compose build
```
2. Start the containers:
```bash
docker-compose up
```

### Update Database Configuration In .env
```bash
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel # Default database created by the Docker setup else you can create your own in mysql container
DB_USERNAME=root
DB_PASSWORD=root
```

### Migrate The Database
1. Access the PHP Container:
```bash
docker exec -it <php-container-name> bash
```
2. Run Migrations:
```bash
php artisan migrate
```

### Access The Application
```bash
http://localhost:8080
```

## Technologies Used
- **Laravel**: A PHP framework used for building the backend logic and routing.
- **The Guardian API**: Provides the latest news articles for various sections.
- **Monolog**: Handles logging of errors and warnings.

## Contributing
Feel free to fork this repository and make your changes. If you would like to contribute, submit a pull request.