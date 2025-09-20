#!/bin/bash

# Rebuild frontend for development
echo "Building frontend..."
cd frontend
npm run build

# Copy built files to public directory
echo "Copying to public directory..."
cd ..
cp -r frontend/dist/* public/
cp 'frontend/.htaccess' public/

