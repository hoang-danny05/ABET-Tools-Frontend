# The Frontend for ABET-Tools

This is a version control tracker of the Frontend. The job of this repository is to simply track the state of the Frontend. 

It is not currently setup to be locally runnable, only on osburn's server. 

## Backing up the frontend 

This requires the ABET private key to be setup. 
1) git clone this repo.
2) cd into `scripts/` and run `copy_from_server.bash`
    - I hardcoded the server IP, so we might need to change this if the step doesn't work
    - The script also assumes you're in `scripts/`, else it will copy the files to who knows where.
4) git add, commit, and push your changes


## Setting up ABET private key

1) download abet ssh key from the CPanel
2) Set correct permissions: `chmod 600 abet`
3) Move it to a convenient spot. usually `.ssh/`
4) `ssh-add $PATH_TO_ABET_PRIVATE_KEY`
5) Use the ABET ssh key password in the discord.
