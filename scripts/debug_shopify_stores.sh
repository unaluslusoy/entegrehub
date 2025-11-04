#!/bin/bash
# Shopify Store Debug Script

cd /home/entegrehub/domains/kargo.entegrehub.com/public_html

echo "=== Checking Shopify Stores ==="
echo ""

# Get database credentials from .env
if [ -f .env.local ]; then
    source .env.local
elif [ -f .env ]; then
    source .env
fi

# Extract database connection details
DB_URL=${DATABASE_URL}
DB_NAME=$(echo $DB_URL | sed -n 's/.*\/\([^?]*\).*/\1/p')
DB_USER=$(echo $DB_URL | sed -n 's/.*:\/\/\([^:]*\):.*/\1/p')
DB_PASS=$(echo $DB_URL | sed -n 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/p')

echo "Database: $DB_NAME"
echo ""

# Check total stores
echo "Total Shopify Stores:"
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) as total FROM shopify_stores;" 2>/dev/null
echo ""

# Check stores by user
echo "Stores grouped by user_id:"
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT 
    user_id,
    COUNT(*) as store_count,
    GROUP_CONCAT(shop_domain) as stores
FROM shopify_stores 
GROUP BY user_id;
" 2>/dev/null
echo ""

# Check active stores
echo "Active stores details:"
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT 
    id,
    user_id,
    shop_domain,
    shop_name,
    is_active,
    sync_status,
    total_orders_synced,
    created_at
FROM shopify_stores 
WHERE is_active = 1
ORDER BY created_at DESC
LIMIT 10;
" 2>/dev/null
echo ""

# Check if any stores have null user_id
echo "Stores with NULL user_id:"
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
SELECT id, shop_domain, created_at 
FROM shopify_stores 
WHERE user_id IS NULL;
" 2>/dev/null
echo ""

echo "=== Done ==="
