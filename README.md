
# SC Live

## [REQUIREMENTS]

- Vagrant (https://www.vagrantup.com/docs/installation/)
- VirtualBox v5.1 (https://www.virtualbox.org/)
- You must have two-factor authentication switched on your Instagram account

## [TO INSTALL]

1) Git clone to a directory
2) Navigate to that directory and run 'vagrant up'
3) SSH into the newly created Vagrant Box by running 'vagrant ssh' and navigate to 'cd /var/www/public'
4) Run 'composer install'

## [TO RUN]

1) Navigate to 'cd /var/www/public' and run...
2) Run 'php scLive setup' followed by your username and password
3) Run 'php scLive login'
4) Run 'php scLive stream:start'

## [COMMANDS WHEN LIVE]

-stop : Stops the stream 
-pin : Add to the end of a comment to pin 
-unpin : Unpins any pinned comments
