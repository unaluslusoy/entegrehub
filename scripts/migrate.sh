#!/bin/bash

# ============================================
# Database Migration Script
# EntegreHub Kargo SaaS System
# ============================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Database credentials from .env
ENV_FILE="/home/entegrehub/domains/kargo.entegrehub.com/public_html/.env"

if [ ! -f "$ENV_FILE" ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    exit 1
fi

# Load database credentials from DATABASE_URL
DATABASE_URL=$(grep DATABASE_URL $ENV_FILE | grep -v '^#' | head -1 | cut -d '=' -f2- | tr -d '"')

# Parse mysql://user:password@host:port/database
DB_USER=$(echo $DATABASE_URL | sed -n 's/.*:\/\/\([^:]*\):.*/\1/p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/p')
DB_HOST=$(echo $DATABASE_URL | sed -n 's/.*@\([^:]*\):.*/\1/p')
DB_NAME=$(echo $DATABASE_URL | sed -n 's/.*\/\([^?]*\).*/\1/p')

echo -e "${GREEN}===========================================  =${NC}"
echo -e "${GREEN}Database Migration Script${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${YELLOW}Database: ${DB_NAME}${NC}"
echo -e "${YELLOW}Host: ${DB_HOST}${NC}"
echo ""

# Migration directory
MIGRATION_DIR="/home/entegrehub/domains/kargo.entegrehub.com/public_html/migrations"
SEED_DIR="$MIGRATION_DIR/seeds"

# Test database connection
echo -e "${YELLOW}Testing database connection...${NC}"
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" > /dev/null 2>&1

if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Cannot connect to database!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Database connection successful${NC}"
echo ""

# Confirm before proceeding
read -p "Do you want to run migrations? This will modify the database. (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Migration cancelled.${NC}"
    exit 0
fi

# Run main schema migration
echo -e "${YELLOW}Running schema migration...${NC}"
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$MIGRATION_DIR/001_initial_schema.sql"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Schema migration completed${NC}"
else
    echo -e "${RED}✗ Schema migration failed!${NC}"
    exit 1
fi

echo ""

# Run seed files
if [ -d "$SEED_DIR" ]; then
    echo -e "${YELLOW}Running seed files...${NC}"
    
    for seed_file in "$SEED_DIR"/*.sql; do
        if [ -f "$seed_file" ]; then
            filename=$(basename "$seed_file")
            echo -e "${YELLOW}  - Running $filename...${NC}"
            mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$seed_file"
            
            if [ $? -eq 0 ]; then
                echo -e "${GREEN}  ✓ $filename completed${NC}"
            else
                echo -e "${RED}  ✗ $filename failed!${NC}"
            fi
        fi
    done
else
    echo -e "${YELLOW}No seed directory found, skipping seeds.${NC}"
fi

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}Migration completed successfully!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""

# Show table count
TABLE_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES" | wc -l)
TABLE_COUNT=$((TABLE_COUNT - 1)) # Remove header line

echo -e "${GREEN}Total tables created: $TABLE_COUNT${NC}"
echo ""

# Show cargo companies count
CARGO_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM cargo_companies" | tail -1)
echo -e "${GREEN}Cargo companies seeded: $CARGO_COUNT${NC}"

# Show subscription plans count
PLAN_COUNT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM subscription_plans" | tail -1)
echo -e "${GREEN}Subscription plans seeded: $PLAN_COUNT${NC}"

echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo -e "  1. Run: ${GREEN}composer install${NC}"
echo -e "  2. Generate encryption key: ${GREEN}php bin/console app:generate-key${NC}"
echo -e "  3. Clear cache: ${GREEN}php bin/console cache:clear${NC}"
echo ""
