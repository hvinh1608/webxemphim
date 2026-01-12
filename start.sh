#!/bin/bash
echo "Starting Laravel server..."
echo "PHP Version: $(php --version | head -1)"
echo "Laravel check: $(php artisan --version 2>/dev/null && echo OK || echo FAILED)"
echo "Starting Apache..."
apache2-foreground


echo "Starting Laravel server..."
echo "PHP Version: $(php --version | head -1)"
echo "Laravel check: $(php artisan --version 2>/dev/null && echo OK || echo FAILED)"
echo "Starting Apache..."
apache2-foreground


