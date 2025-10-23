#!/bin/bash

# Rebuild frontend for development
echo "Building frontend..."
cd frontend
rm -rf node_modules/.vite dist
npm run build

# Copy built files to public directory
echo "Copying to public directory..."
cd ..
# Ensure target public directory exists
mkdir -p ../snappy/public

# Copy special files first (so they are preserved)
if [ -f frontend/.htaccess ]; then
	cp -f frontend/.htaccess ../snappy/public/
fi
if [ -f frontend/.env ]; then
	cp -f frontend/.env ../snappy/public/
fi

# Remove everything in ../snappy/public except .htaccess and .env using find
if [ -d ../snappy/public ]; then
	find ../snappy/public -mindepth 1 -maxdepth 1 \
		! -name '.htaccess' ! -name '.env' -exec rm -rf -- {} +
fi

# Copy built files (index.html and assets) into ../snappy/public
if [ -d public ]; then
	cp -r public/* ../snappy/public/ 2>/dev/null || echo "No public contents to copy"
else
	echo "No public folder to copy"
fi

# recreate symlinks to the php code for immediate testing in virtual host   
ln -s ~/Projects/fault-reporter/backend ~/Projects/snappy/public/backend
ln -s ~/Projects/fault-reporter/snappy-admin ~/Projects/snappy/public/snappy-admin
# capture logs outside of the web root so they can be viewed directly
ln -s ~/Projects/fault-reporter/all-logs ~/Projects/snappy/public/all-logs
# capture images outside of the web root so they kept available for testing after rebuild
ln -s ~/Projects/fault-reporter/uploads ~/Projects/snappy/public/uploads