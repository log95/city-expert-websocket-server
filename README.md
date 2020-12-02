# City expert websocket server

### About
WS server for [city-expert-backend](https://github.com/log95/city-expert-backend) and 
[city-expert-frontend](https://github.com/log95/city-expert-frontend).

Based on [Ratchet](http://socketo.me/) as ws server and 
[ZeroMQ](https://zeromq.org/) as library for messages from backend app.

Serve only authorized users with [JWT](https://en.wikipedia.org/wiki/JSON_Web_Token).

### Start app
- Need `docker`, `docker-compose` installed
- Clone repository. `git clone https://github.com/log95/city-expert-websocket-server.git`
- Create .env config file. `cp .env.example .env`
- Up environment. `./local-env.sh up`
