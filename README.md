## 🚀 Install Backend

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate
php artisan serve
```

### 🧩 Install Frontend

```bash
npm install
npm run build
npm run dev
```

### 🧩 Push Folder

```bash
git add backend
git commit -m "Add backend master data CRUD modules"
git push origin main
```

### 🧩 Pull Folder

```bash
git pull origin main
git pull --rebase origin main
```
