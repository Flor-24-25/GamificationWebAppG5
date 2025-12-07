#!/bin/bash
# Gamification Setup Script

echo "================================"
echo "Gamification System Setup"
echo "================================"
echo ""

# Check if MySQL is available
command -v mysql &> /dev/null
if [ $? -eq 0 ]; then
    echo "✓ MySQL found"
    echo "Running database setup..."
    mysql -u root login < database/gamification_tables.sql
    echo "✓ Database tables created"
else
    echo "⚠ MySQL command not found"
    echo "Please manually run: database/gamification_tables.sql in phpMyAdmin"
fi

echo ""
echo "Setting up environment variables..."

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ Created .env file"
    echo "⚠ Please edit .env and add your OpenAI API key"
else
    echo "✓ .env file already exists"
fi

echo ""
echo "Setup Complete!"
echo ""
echo "Next steps:"
echo "1. Make sure XAMPP is running"
echo "2. Login to your account"
echo "3. Navigate to: http://localhost/login-form-with-database-connection-main/public/dashboard.php"
echo "4. Click 'Play Game' to start"
echo ""
echo "For OpenAI hints:"
echo "5. Edit .env and add your OpenAI API key"
echo "6. Uncomment the OpenAI initialization in game.php"
echo ""
