docker container stop php-server-running
docker container rm php-server-running
docker build -t php-server-image .
docker run -d -p 80:80 -p 443:443 --name php-server-running php-server-image
