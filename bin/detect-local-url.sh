#!/bin/bash

# Detect Local WordPress Site URL
# Tests common Local URL patterns to find the accessible site

set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== Local WordPress URL Detector ===${NC}"
echo ""

# Common Local URL patterns
URLS=(
    "http://post-formats-test.local"
    "http://postformatstest.local"
    "http://post-formats-test.test"
    "http://localhost:10003"
    "http://localhost:10004"
    "http://localhost:10005"
    "http://localhost:10006"
    "http://localhost:10007"
    "http://localhost:10008"
    "http://localhost:10009"
    "http://localhost:10010"
)

echo "Testing common Local URL patterns..."
echo ""

FOUND_URL=""

for url in "${URLS[@]}"; do
    echo -n "Testing ${url}... "

    # Test if URL responds
    if curl -s -o /dev/null -w "%{http_code}" --connect-timeout 2 "${url}" 2>/dev/null | grep -q "200\|302\|301"; then
        echo -e "${GREEN}✓ FOUND!${NC}"
        FOUND_URL="${url}"
        break
    else
        echo -e "${RED}✗ Not accessible${NC}"
    fi
done

echo ""

if [ -n "$FOUND_URL" ]; then
    echo -e "${GREEN}=== Site URL Detected ===${NC}"
    echo -e "URL: ${FOUND_URL}"
    echo ""
    echo "Updating .env file..."

    # Update .env file
    if [ -f ".env" ]; then
        sed -i.bak "s|WP_BASE_URL=.*|WP_BASE_URL=${FOUND_URL}|" .env
        rm .env.bak
        echo -e "${GREEN}✓ .env file updated${NC}"
    else
        echo -e "${YELLOW}⚠ .env file not found${NC}"
    fi

    echo ""
    echo "You can now run tests:"
    echo "  npm run test:a11y"
    echo "  npm run test:e2e"
else
    echo -e "${YELLOW}=== No accessible URL found ===${NC}"
    echo ""
    echo "Please ensure your Local site is running, then:"
    echo ""
    echo "1. Open Local app"
    echo "2. Start 'post-formats-test' site"
    echo "3. Copy the site URL from Local"
    echo "4. Update .env file:"
    echo "   WP_BASE_URL=YOUR_ACTUAL_URL"
    echo ""
    echo "Common Local URLs:"
    echo "  - http://post-formats-test.local"
    echo "  - http://localhost:10XXX (check Local for port)"
fi
