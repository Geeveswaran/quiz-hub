# Quiz Master Hub - Docker Setup Guide

## Installation Steps

### 1. Install Docker Desktop
- **Windows**: Download from https://www.docker.com/products/docker-desktop/
- Run the installer and complete setup
- Restart your computer
- Verify: Open PowerShell and run `docker --version`

### 2. Clone/Navigate to Project
```powershell
cd d:\Quiz-Master-Hub
```

### 3. Build and Run with Docker
```powershell
docker-compose up --build
```

This will:
- Build the PHP 8.3 image with MongoDB extension pre-installed
- Create a container and run the app on `http://localhost:8000`
- Mount your project files (live changes reflected immediately)
- Set MongoDB Atlas connection string automatically

### 4. Access Your App
Open browser: **http://localhost:8000**

### 5. Verify MongoDB Extension
```powershell
docker exec quiz-master-php php -m | findstr mongodb
```

Expected output: `mongodb`

## Commands

### Start the app
```powershell
docker-compose up
```

### Stop the app
```powershell
docker-compose down
```

### Rebuild after Dockerfile changes
```powershell
docker-compose up --build
```

### Run PHP commands inside container
```powershell
docker exec quiz-master-php php test_mongodb.php
```

### View logs
```powershell
docker-compose logs -f
```

## Current System Status

✅ **JSON File Storage**: Fully functional (no changes needed)
✅ **Code Files**: All syntax validated
✅ **MongoDB URI**: Already configured in `.env`
✅ **Study Materials**: Upload feature complete
✅ **Quiz Features**: Anti-cheating, countdown timers working

## After Docker Setup

Once running in Docker:
1. Visit http://localhost:8000/test_mongodb.php to verify MongoDB extension
2. All data will sync to MongoDB Atlas automatically
3. JSON files remain as fallback

## Troubleshooting

**"docker: command not found"**
- Restart PowerShell/Terminal after Docker Desktop installation

**Container exits immediately**
- Check logs: `docker-compose logs`
- Rebuild: `docker-compose up --build`

**Port 8000 already in use**
- Change in docker-compose.yml: `"8001:8000"` instead of `"8000:8000"`

**MongoDB connection fails**
- Verify .env file has `MONGODB_URI` set
- Check internet connection (MongoDB Atlas requires network access)
- Verify IP whitelist on MongoDB Atlas includes your machine
