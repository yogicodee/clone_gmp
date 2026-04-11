## 🚀 Install Backend

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate
php artisan serve
```

## 🧩 Install Frontend

```bash
npm install
npm run build
npm run dev
```

### 🧩 Push Folder Frontend

```bash
git add frontend
git commit -m ""
git push origin main
```

### 🧩 Push Folder Backend

```bash
git add backend
git commit -m ""
git push origin main
```

### 🧩 Pull Folder Frontend

```bash
git fetch origin
git restore --source=origin/main frontend
```
### 🧩 Pull Folder Backend

```bash
git fetch origin
git restore --source=origin/main backend
```

