# Installation

### 1. Clone repository

```
git clone https://github.com/pankajsondagar/laravel-vue-trading-platform.git
cd laravel-vue-trading-platform
```

### 2. Install Laravel dependencies
```
composer install
```

### 3. Copy environment file
```
cp .env.example .env
```

### 4. Configure environment (Edit .env file)
Set database credentials:

```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=trading_platform
DB_USERNAME=root
DB_PASSWORD=
```

Set Pusher credentials:

```
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 5. Generate application key
```
php artisan key:generate
```

### 6. Run migrations
```
php artisan migrate
```

### 7. Seed database
```
php artisan db:seed
```

### 10. Create Vue.js frontend with Vite
```
npm install @vitejs/plugin-vue
```

### 11. Install dependencies
```
npm install
```

### 12. Install Tailwind CSS
```
npm install -D tailwindcss postcss autoprefixer
```

### 13. Install additional packages
```
npm install axios pinia vue-router vue-toastification@next
npm install laravel-echo pusher-js
```

### Terminal - 1
```
php artisan serve
```

### Terminal - 2
```
npm run dev
```

### Visit
```
http://localhost:8000
```