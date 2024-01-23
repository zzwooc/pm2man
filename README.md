# Documentation

#### PM2MAN

1. Install

```bash
npm install
```

2. Build

```bash
npm run build
```

3. Run

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html;
    index index.html
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

#### PM2SRV

[PM2SRV](https://github.com/zzwooc/pm2srv)

#### Screenshot

![screenshot.png](./screenshot.png)