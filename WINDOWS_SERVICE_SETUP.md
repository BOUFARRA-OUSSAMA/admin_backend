# Laravel Queue Worker - Windows Service Setup

## Using NSSM (Non-Sucking Service Manager)

### 1. Download NSSM
- Download from: https://nssm.cc/download
- Extract to: `C:\nssm\`

### 2. Install Service
Open Command Prompt as Administrator:
```cmd
cd C:\nssm\win64
nssm install "Laravel Queue Worker"
```

### 3. Configure Service
- **Application**: `C:\php\php.exe` (or wherever your PHP is installed)
- **Startup Directory**: `c:\Users\Sefanos\Desktop\n8n\Frontend\admin_backend`
- **Arguments**: `artisan queue:work --daemon --tries=3 --timeout=60 --sleep=3 --max-jobs=1000`

### 4. Start Service
```cmd
nssm start "Laravel Queue Worker"
```

### 5. Service Commands
```cmd
# Check status
nssm status "Laravel Queue Worker"

# Stop service
nssm stop "Laravel Queue Worker"

# Remove service
nssm remove "Laravel Queue Worker"
```

## Benefits
- ✅ Starts automatically when Windows boots
- ✅ Restarts automatically if it crashes
- ✅ Runs in background (no visible window)
- ✅ Managed like any Windows service
