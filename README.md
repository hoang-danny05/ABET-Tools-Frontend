# The Frontend-Backend interface for ABET-Tools

This is a version control tracker of the PHP Backend that controls how the different components interface with the browser.. The job of this repository is to simply track the state of the Backend and how it interfaces with the frontend. 

It is not currently setup to be locally runnable, only on osburn's server. 

## Backing up the codebase 

This requires the ABET private key to be setup. 
1) git clone this repo.
2) cd into `scripts/` and run `copy_from_server.bash`
    - I hardcoded the server IP, so we might need to change this if the step doesn't work
    - The script also assumes you're in `scripts/`, else it will copy the files to who knows where.
4) git add, commit, and push your changes

## Running the application locally

This requires docker engine AND docker compose to be installed. Please refer to the official Docker documentation. This also requires you to have the cPanel server private keys created. It also requires that you have basic tools like a bash command line and git.

[Docker Install](#docker-installs)
[Abet Private Key](#abet-private-key-setup)

1) cd into `scripts/` and run `bash sync_private_files.bash`
2) cd into `docker/` and create a `.env` file. `env.demo` is a format file for .env, you should follow that
3) cd into `docker/` and run `docker compose up`
4) you can visit [https://localhost:8080](https://localhost:8080)

## Docker Installs

### Linux Docker Install

[Docker engine](https://docs.docker.com/engine/install)
[Docker compose](https://docs.docker.com/compose/install)

### Windows Docker Install

[Docker desktop](https://docs.docker.com/desktop/setup/install/windows-install/) (Installs both!)

## ABET private key setup

from a bash terminal

1) download abet ssh key from the CPanel
2) Set correct permissions: `chmod 600 abet`
3) Move it to a convenient spot. usually `.ssh/`
4) `eval "$(ssh-agent -s)"`
5) `ssh-add $PATH_TO_ABET_PRIVATE_KEY`
6) Use the ABET ssh key password in the discord.

Note: you can automate step 4 and 5 if you create your own private key and put these commands in your `~/.bashrc`

## Important Files
