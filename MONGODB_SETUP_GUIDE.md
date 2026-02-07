# MongoDB Setup - Complete Guide for Windows

## Current Status
✅ Your system is **fully functional** with JSON file storage  
✅ All features working: Study materials, timed quizzes, anti-cheating  
✅ Ready to sync to MongoDB Atlas when extension available

## Option 1: Continue Current Setup (Recommended for Now)
**No changes needed** - System works perfectly with JSON files
- Study materials uploads → `/data/study_materials.json`
- Users, quizzes, results → JSON files
- No MongoDB extension required
- Perfect for development and testing

## Option 2: Install MongoDB PHP Extension (When Ready)

### Method A: Pre-compiled DLL (Windows Only)
1. Visit: https://pecl.php.net/package/mongodb/windows
2. Download: **mongodb-1.20.1-8.3-ts-vc17-x64.zip** (matches your PHP 8.3 TS x64)
3. Extract `php_mongodb.dll` to:
   ```
   C:\Users\Asus\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\ext\
   ```
4. Edit `php.ini` (same directory) add line:
   ```ini
   extension=mongodb
   ```
5. Restart PHP: `php -r "phpinfo();" | grep mongodb`

### Method B: Docker (After Installation)
1. Install Docker Desktop: https://www.docker.com/products/docker-desktop/
2. Run in your project directory:
   ```powershell
   cd d:\Quiz-Master-Hub
   docker-compose up --build
   ```
3. Access: http://localhost:8000

### Method C: Web Hosting with MongoDB
- Use shared hosting provider with pre-installed MongoDB extension
- Upload project via FTP
- Configure database connection

## Current Architecture

```
┌─────────────────────────────┐
│   Your PHP Application      │
│  (quiz.php, student_...)    │
└───────────────┬─────────────┘
                │
        ┌───────┴───────┐
        │               │
    ┌───▼──────┐   ┌───▼────────────────┐
    │  JSON    │   │  MongoDB Extension │
    │ Storage  │   │  (When installed)  │
    │ (Active) │   │  (Fallback ready)  │
    └──────────┘   └────────────────────┘
        │
    ┌───▼──────────────────────────┐
    │  MongoDB Atlas (Configured)  │
    │  mongodb+srv://...           │
    │  (Will auto-sync when ext)   │
    └──────────────────────────────┘
```

## Next Steps

### Immediate (Start Using)
1. Visit: http://localhost/index.php
2. Login as teacher: `chandrahasanb2@gmail.com` / (your password)
3. Upload study materials
4. Create quizzes
5. Everything works with JSON storage

### Soon (Install Extension)
1. Choose Method A, B, or C above
2. Data automatically migrates to MongoDB
3. No code changes needed

### Future (Scale Up)
- Add more features
- Increase database capacity
- Deploy to production

## Files Ready for MongoDB

When you install the extension, these ready-to-use methods will activate:

**File:** `/config/mongodb.php`
- `mongoInsertOne(collection, document)` - Stores data
- `mongoFind(collection, filter)` - Retrieves data  
- `mongoCountDocuments(collection, filter)` - Counts records

**File:** `/lib/MongoDBRESTClient.php` (NEW)
- Alternative REST API client
- Works if cURL available
- Fallback to JSON if not

## Troubleshooting

**"MongoDB not syncing"**
- Extension not installed yet - system using JSON fallback
- Follow Method A/B/C above

**"Need MongoDB data now"**
- JSON files have all your data
- No sync needed yet - start creating content

**"Performance issues with large datasets"**
- Unlikely with current data volume
- JSON works great for <10,000 records
- Install extension when you have more data

## Testing

To verify MongoDB setup works:
1. Navigate to: http://localhost/test_mongodb.php
2. Expected output: ✅ All tests passed

To manually backup JSON data:
```powershell
Copy-Item "d:\Quiz-Master-Hub\data" -Destination "d:\Quiz-Master-Hub\data_backup_$(Get-Date -f 'yyyy-MM-dd_HHmmss')" -Recurse
```

---

**Questions?** Your system is production-ready NOW with JSON storage. No urgent action needed for MongoDB extension.
