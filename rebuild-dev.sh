#!/bin/bash

# Rebuild frontend for development
echo "Building frontend..."
cd frontend
rm -rf node_modules/.vite dist
npm run build

# Copy built files to public directory
echo "Copying to public directory..."
cd ..
cp -r frontend/dist/* public/ 2>/dev/null || echo "No dist folder to copy"
cp 'frontend/.htaccess' public/
cp 'frontend/.env' public/
ln -s ~/Projects/fault-reporter/backend ~/Projects/fault-reporter/public/backend 
ln -s ~/Projects/fault-reporter/all-logs ~/Projects/fault-reporter/public/all-logs
