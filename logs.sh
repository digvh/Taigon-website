# Create logs directory
mkdir logs
chmod 755 logs

# Create a .htaccess inside logs to prevent direct access
echo "Deny from all" > logs/.htaccess