# postReceiver

This application serves as a generic SAML SP endpoint that will decode incoming SAML and display relevant data.

## How to use

This container can be run in either HTTP-only mode, or with HTTPS enabled as well.
* To run in HTTP only mode:
  1) Rename the "docker-compose-http.yaml" to "docker-compose.yaml"
  2) Run "docker-compose up -d"

* To run with HTTPS enabled:
  1) Copy your server public certificate to "httpd/server.crt"
  2) Copy your server's private key to "httpd/server.key"
  3) Rename the "docker-compose-https.yaml" to "docker-compose.yaml"
  4) Run "docker-compose up -d"
