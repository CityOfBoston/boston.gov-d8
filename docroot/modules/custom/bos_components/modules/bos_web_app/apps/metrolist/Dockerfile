# Use the official Node.js 14 image as the base image
FROM node:14

# Set the working directory inside the container
WORKDIR /app

# Copy the package.json and package-lock.json files to the container
COPY package*.json ./

# Install Node.js dependencies
RUN npm install --verbose

# Copy the entire project to the working directory in the container
COPY . .

# Designate the volume
VOLUME ["/app/dist"]