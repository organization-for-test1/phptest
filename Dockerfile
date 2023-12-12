# Use the official PHP with Apache image
FROM php:8.2.0-apache

# Set the working directory in the container
WORKDIR /var/www/html

# Copy the entire application code to the working directory
COPY . .

# Expose port 80 for the Apache server
EXPOSE 80

# Enable Apache modules and restart Apache
RUN a2enmod rewrite && service apache2 restart

# The CMD command runs when the container starts
CMD ["apache2-foreground"]

