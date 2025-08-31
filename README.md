# Drupal Gazette Notices Browser

A Drupal 10 module that fetches and displays notices from The Gazette UK API with pagination support. This module provides a clean table view of gazette notices including ID, title, and status, with next/previous navigation controls.

## Features

- Fetches notices from The Gazette UK API (https://www.thegazette.co.uk/all-notices/notice/data.json)
- Displays notices in a responsive table with ID, title, and status columns
- Implements pagination with next/previous navigation controls
- Handles API errors gracefully
- Built with testable code following Drupal best practices
- No external dependencies beyond Drupal core

## Requirements

- Drupal 10.x
- PHP 8.0 or higher
- Composer
- Internet connection to access The Gazette API

## Installation

1. Download the module:
   ```bash
   cd /path/to/your/drupal/project
   git clone https://github.com/yourusername/drupal-gazette-notices-browser.git modules/custom/gazzet_notices