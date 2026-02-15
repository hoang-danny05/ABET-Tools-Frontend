# Environment Variables

Environment variables are how we supply our applications with credentials WITHOUT hard-coding them.
This allows us to track the project without exposing important information.
These files are typically named `.env` and will NOT be tracked by version control.

## `docker/.env`

This .env file is used by docker-compose to supply important information, such as database credentials or API keys.
It ensures that all containers have the same credentials.

