# Installing Machinon - The complete guide

This guide merges the three guides for rpi-setup, machinon_client and agent_machinon packages.

### Install Raspbian

NOOBS is a very simple way to create an SD with Raspberry linux image from any OS (windows, osx, etc...) as it only implies downloading a zip file and unzipping it into a fresh formatted SD card. 
However, if you plan to clone your SD card after you finish this installation (using `dd` or any other SD cloning software), to put it into multiple Raspberries, creating a traditional Raspbian image card is recommended.

#### Using Noobs

1. Download NOOBS installer from 
https://www.raspberrypi.org/downloads/noobs/
2. Formatting SD Card in fat32 with SDFormatter (Mac) or Windows similar tool.
3. Unzip NOOBS zip file into SD card. Ensure the SD card's root folder shows a bunch of files. If the root folder contains only a NOOBS folder, you've done wrong. Check the INSTRUCTIONS-README.txt file inside the NOOBS folder for detailed explanation.
4. Eject SD card safely, put it into Raspberry, attach keyboard, network cable, monitor, etc... and boot.
5. A very easy installation wizard will appear, choose to install Raspbian Lite and follow instructions. You may need to configure your WiFi if not using cabled networking with DHCP. 
6. The installation takes some minutes depending of your network connection.

#### Using Raspbian image

1. Download the Raspbian Stretch Lite OS .zip image from the Raspbian site.
2. "Burn" the image into SD card using one of the various tools available on internet. For OSX I used `balenaEtcher`, which can burn direct .zip images.
3. Eject SD card safely, put it into Raspberry, attach keyboard, network cable, monitor, etc... and boot.


### Post installation setup:

Once rebooted and the screen shows the classic linux login.
Use username `pi` with password `raspberry` to login.

Run the Raspbian configuration tool

```
sudo raspi-config
```

0. Update raspi-config tool (8th option)
1. Set new password for user 'pi', chose anything you want.
2. Network options :
* Set hostname to something like `machinonNN` where NN is a number.
* Do not enable predictable network interface names.
* You can enable WiFi and connect to internet through it**

** agent-machinon uses the eth0 interface MAC Address as Re:Machinon's *MUID* (Machinon Unit ID). So, even if you are using WiFI to access internet with the Raspberry, don't disable eth0 interface.

3. Boot options : 
* Boot on console (WITHOUT autologin)
4. Localization options :
* Timezone
	* Choose "None of the above", then  UTC
* Locales
	* Usually choosing EN_GB@UTC-8 is fine. Raspbian detects your location so, if you use another locale, install it to avoid Raspbian dropping Locale error messages. 
5. Interfacing options (based on Matthew's instructions)
* Enable SPI
*  Enable I2C
*  Enable Serial 
	* Do NOT enable login shell over serial.
* Enable remote command line through SSH. 
	* Not required but it allows to do the rest of the setup through SSH, so you can detach the Raspberry from monitor/keyboard/etc...
6. Advanced options :
*  Expand filesystem to fill card. 
	* If you installed Raspbian using NOOBS this step is not required.
	* Latest Raspbian image also performs this procedure during the first boot.
*  Change GPU memory to 16 MB


### Updating the SO

Once you login again in the Pi, update the operative system

```
sudo apt-get update
sudo apt-get upgrade
sudo apt-get dist-upgrade
sudo apt-get clean
```

### Adding system overlays

Edit  `/boot/config.txt`  and add the following lines 
enable_uart is probably already in the file due to previous raspi-config setup
    
```
dtoverlay=sc16is752-spi1,24
dtoverlay=i2c-rtc,mcp7941x
dtoverlay=pi3-act-led, gpio=26
# Change BT to mini-uart (Pi3 only)
dtoverlay=pi3-miniuart-bt
# Optionally disable wifi
dtoverlay=pi3-disable-wifi
# Optionally disable bluetooth
dtoverlay=pi3-disable-bt
# Enable UART Pi3 and Jessie or later (can also be done in raspi-config)
enable_uart=1
```

### Setting IP address

I think this is absolutely NOT necessary because all routers have DHCP enabled  (usually). But just in case I followed up the instructions

Edit  `/etc/dhcpcd.conf`  
    
```
nano /etc/dhcpcd.conf
```

And uncomment or add these lines (change to suit your network settings):

```
interface eth0
static ip_address=192.168.1.15/24
static routers=192.168.1.1
static domain_name_servers=192.168.1.1
```
    
Alternatively, edit the "fall back to static IP" section to make the Pi fall back to that static IP if DHCP fails.
    
Reboot to apply all previous changes

```
sudo reboot
```

### Disable bluetooth service from starting 

This prevents service startup errors:

```
sudo systemctl disable hciuart
```

### Allow applications to use serial port

Edit `/boot/cmdline.txt` and remove the text `console=serial0,115200` 
See [https://www.raspberrypi.org/documentation/configuration/uart.md](https://www.raspberrypi.org/documentation/configuration/uart.md) for more info.

>Jose : I didn't had to do this, there wasn't that string in the file

### Adding modules

Edit  `/etc/modules`  and add a new line with  `rtc-mcp7941x`

### Setting hardware clock

Edit  `/lib/udev/hwclock-set`  and comment out (add # to start of lines) the lines:

```
if [ -e /run/systemd/system ] ; then
    exit 0
fi 
```

>Jose : Is commenting the systemd failure check safe?

Reboot and check that the Pi has correct time from network. Then optionally manually set HW clock with  `sudo hwclock -w`  to write system time to HW clock. The Pi will automatically load the time/date from the HW clock at boot. This can can be forced manually with  `sudo hwclock -r`  to set the system clock from the HW clock. The Pi does an NTP update of system clock at boot, and then every 1024 secs (17 mins) thereafter, and sets the RTC from this. ADD HOW TO CHECK TIME VIA CLI


### Aliasing serial ports nodes

Add permanent aliases for the SPI UARTs (Domoticz does not show port names like "ttySC1", so here we create aliases to "serial2" for RS485 and "serial3" for machinon config):
1.  create a new udev rules file  `/etc/udev/rules.d/98-minibms.rules`  with the following content:  

```KERNEL=="ttySC0" SYMLINK="serial485"```

2.  Save  and reboot
3.  Run  `ls -l /dev`  command and ensure serial0 and serial1 appear in the results as aliases for the Pi internal ports.

# Install Domoticz

`curl -L install.domoticz.com | sudo bash`

> ***IMPORTANT!!***
The step in the Matthew's document that says you have to create a service for domoticz at `/etc/systemd/system/domoticz.service` is NOT needed at all.  Or at least I don't find any reason to do it.
Domoticz setup already setups a service in the systemctl and can be managed using
```
sudo service domoticz start|stop|restart|status|etc...
```

# Installing Nginx + PHP

This covers all requirements for rpi_setup and machinon_client packages.

```
sudo apt-get install nginx php-cli php-common php-fpm php-cgi php-pear php-mcrypt php-curl memcached ssl-cert
```

# Setting up Nginx and installing machinon_config app


#### Create server block (a.k.a. virtual host) 
> Jose : The comments shown in the GitHub document look funny. Nginx comments start by \# and not by #.
> I've cleaned up the config file removing all comments , too many information...
> This nginx server block makes basically the same as the one described in machinon_client package.
> The SSL in the raspberry's web server is NOT required, as the SSH tunnel already adds the encryption layer in the communications. 
> This step (creating this server block) could totally be skipped, as machinon_client will do the work, but at the moment, I'll just change this server block's port from 80 to 81 and remove the SSL settings.

```
cd /etc/nginx/sites-available
sudo nano nginx-machinon.conf
```

Put the following config in that file:

```
# Machinon Web Config Interface and Proxy Server Configuration
server {
    listen 81 default_server;
    root /var/www/html;
    index index.html index.htm index.php;
    error_page 404 /error404.html;
    server_name _;
    server_name_in_redirect off;
    #absolute_redirect off; # only in v1.11.8 or later
    #rewrite_log on;
    location = / {
        try_files $uri /index.html @domo;
    }
    location = /config {
        rewrite ^ config/ redirect;
    }
    location = /machinon {
        rewrite ^ machinon/ redirect;
    }
    location / {
        try_files $uri =404;
    }
    location @domo {
        rewrite ^ machinon/ redirect;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
    }
    location = /config/ {
        rewrite ^ index.php redirect;
        try_files $uri $uri/ =404;
    }
    location /machinon/ {
        proxy_pass http://localhost:8080/;
    }
    location ~ /\.ht {
        deny all;
    }
    location ~\.sh$ {
        deny all;
        return 404;
    }
}
```

Reload config (or start server):
```
sudo service nginx restart
```
You can try in your browser http://192.168.1.15:81
It should take you directly to the Domoticz screen.


### Setting serial port permissions for nginx

Set user/group permissions to allow NGINX group/user www-data to access the serial port:

```
sudo usermod -a -G dialout www-data
sudo usermod -a -G www-data pi
```

### Clone the machinon_config app repository

> Jose : This code and the machinon_client code, as well as the previous described Nginx Server Block, need to be "merged/reorganized". This is okay, my code was already prepared for this.

```
cd /var/www/html
sudo git clone https://github.com/EdddieN/machinon_config 
sudo mv machinon_config config
```

You can access the Machinon's config app going to 

http://192.168.1.15:81/config/


# Installing Machinon-Client on your Raspbian


### Create server block file
```
sudo nano /etc/nginx/sites-available/machinon_client
```

Put the next contents on it

```
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name localhost;
    root /opt/machinon_client/public;
    index index.php;
    location / {
        try_files $uri $uri/ =404;
    }
    # Proxying to the Domoticz own server (default port 8080)
    location /domoticz/ {
        auth_request /auth.php;
        proxy_pass http://127.0.0.1:8080/;
        proxy_set_header Host $host ;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        add_header Front-End-Https on;
        proxy_redirect off;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
        fastcgi_read_timeout 600;
    }
    location ~ /\.ht {
        deny all;
    }
    error_page 401 = @error401;
    location @error401 {
        return 302 /index.php;
    }
}
```

Now enable the Server Block

```
sudo ln -s /etc/nginx/sites-available/machinon_client /etc/nginx/sites-enabled/machinon_client
```

### Download the current machinon_client app from GitHub

As the repository is private, you'll be asked for your GitHub user and password, that's okay.

```
cd /opt
sudo git clone https://github.com/EdddieN/machinon_client.git
sudo chown pi:pi -R machinon_client
```

### Setup machinon_client

The app comes with a default configuration file, you just have to rename it.

```
cd /opt/machinon_client/config
mv config.example.php config.php
```

### Start the Nginx Service

Register the Nginx service to run on boot and restart it

```
sudo systemctl enable nginx
sudo service nginx restart
```
 
# Installing Agent-Machinon 

### Install Python 3.5+ and required libraries

Change the package versions accordingly to your Raspbian repository available version.

```
sudo apt-get install python3 python3-pip
sudo -H pip3 install paho-mqtt python-dotenv
```

### Install autossh

This app is required to open the SSH tunnels and keep them opened without timeouts.

```
sudo apt-get install autossh
```

### Download the current agent_machinon app from GitHub

As the repository is private, you'll be asked for your GitHub user and password, that's okay.

```
cd /opt
sudo git clone https://github.com/EdddieN/agent_machinon.git
sudo chown pi:pi -R agent_machinon
cd agent_machinon
```

### Installing SSH Re:Machinon server  key and signature

A public key is needed to let the app open the link with the  Re:Machinon server.

> Jose :  Remember all this will be downloaded / installed internally or generated in the Pi and installed on the server somehow when automating the installation.
> At the moment I'll send the key to you by email. Until we have this installed in a safer server this is on hold.

### Copy the contents of the key I've sent you in this file and set the  right permissions

```
sudo nano /etc/ssh/remachinon_rsa_key.pem 
sudo chmod 400 /etc/ssh/remachinon_rsa_key.pem
```

### Preload the Re:Machinon server signature

> Jose : This step is VERY IMPORTANT!!! Don't change anything of this command, it must go exactly as it is!

```
sudo ssh-keygen -R re.machinon.com 
ssh-keyscan re.machinon.com | sudo tee -a /etc/ssh/ssh_known_hosts
```

# Setup agent_machinon
***LOGIC ENERGY LTD. EMPLOYEES ONLY***

The app provides a sample .env.example file as template but we will create a new .env file configured to use the re.machinon.com site.

```
sudo nano .env
```
Put on it the following contents, save and exit:

**In the MQTT_SERVER_PASSWORD line you must write the password I sent by email**

```
# MQTT Broker definitions  
MQTT_SERVER_HOST=re.machinon.com  
MQTT_SERVER_PORT=1883  
MQTT_SERVER_PORT_SSL=8883  
MQTT_SERVER_USE_SSL=True  
MQTT_SERVER_USERNAME=remachinon  
MQTT_SERVER_PASSWORD=password  
MQTT_CERTS_PATH=/etc/ssl/certs  
  
# MQTT client and topic definitions  
MQTT_CLIENT_ID_PREFIX=agent_machinon:  
MQTT_TOPIC_PREFIX_REMOTECONNECT=remote  
  
# SSH Tunnel details  
SSH_HOSTNAME=re.machinon.com  
SSH_USERNAME=remachinon
SSH_KEY_FILE=/etc/ssh/remachinon_rsa_key.pem  
  
# Remachinon API base URL  
REMACHINON_API_URL=http://${SSH_HOSTNAME}/api/v1  
  
# script user must have write access to this file or folder  
LOG_FILE=tunnel-agent.log
```

### Installing agent_machinon as service

You have to create a new service and put some code on it
```
sudo nano /etc/systemd/system/agent_machinon.service
```
Write in the service file que following code, save and exit

```
# Service for Logic Energy Re:Machinon Tunnel Agent  
[Unit]  
       Description=agent_machinon_service  
[Service]  
       User=pi  
       Group=users  
       ExecStart=/usr/bin/python3 /opt/agent_machinon/tunnel-agent.py
       WorkingDirectory=/opt/agent_machinon/ 
       Restart=always  
       RestartSec=20  
       #StandardOutput=null  
[Install]  
       WantedBy=multi-user.target
```

Register and start the new service

```
sudo systemctl daemon-reload  
sudo systemctl enable agent_machinon.service  
sudo systemctl start agent_machinon.service
```

### Getting your device's MUID

Let's identify your Raspberry MUID (the ethernet MAC address in use), which you'll need to register the device in Re:Machinon. 

```
cat tunnel-agent.log | grep "remote"
```
If the agent is running correctly, you'll get a message like this
```
MQTT Subscribed to 'remote/B827EB8B4A89' QOS=(0,)
```
Copy the hexadecimal value **after** `remote/` , that's the device's MUID!

If the tunnel-agent.log does not exist please re-check all the previous steps, as something's not working.

### Debugging possible errors

In case something goes wrong, you can always run agent_machinon directly from command line:

```
cd /opt/agent_machinon
env python3 tunnel-agent.py
```

If the app is running properly you'll see the app connects to MQTT server and waits for incoming commands. Otherwise it will drop some Python errors.

### Monitoring Agent-Machinon

You can also check Agent Machinon while the service is running by watching the log file. 
This command will continuously show the log contents until Ctrl+C is pressed:

```
cd /opt/agent_machinon
tail -f tunnel-agent.log
```
# Now what?

Visit Re:Machinon portal, join up, register your device using the MUID and you're ready to go!

http://re.machinon.com


> Written with [StackEdit](https://stackedit.io/).
