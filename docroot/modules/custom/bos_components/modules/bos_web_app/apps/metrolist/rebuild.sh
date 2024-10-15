#!/bin/bash

# Define source and destination directories
SOURCE_DIR="./src/components"
DESTINATION_DIR="../boston.gov-d8/docroot/modules/custom/bos_components/modules/bos_web_app/apps/metrolist/src/components"

# Function to show differences and confirm changes
function show_diff_and_confirm() {
    echo "Checking for differences between $SOURCE_DIR and $DESTINATION_DIR..."
    
    # Show the differences between source and destination directories
    diff_output=$(diff -rq "$SOURCE_DIR" "$DESTINATION_DIR" | awk \
        '/Only in/ {
            split($0, parts, ": ");
            system("echo \"New file: " parts[2] "\"")
        }
        /differ$/ {
            system("echo \"Updated file: " $2 "\"")
        }')
    
    if [[ -z "$diff_output" ]]; then
        echo "No differences found. No files will be copied."
    else
      echo "Differences found:"
      echo "$diff_output"
    fi 

    read -p "Do you want to accept these changes and proceed with bootstrapping? (y/N): " confirm
    if [[ "$confirm" != "y" ]]; then
        echo "Changes not accepted. Exiting..."
        exit 0
    fi

    return 0
}

# Execute the diff and confirmation process
show_diff_and_confirm

# Copy only changed files from the source to the destination
echo "Copying changed files from $SOURCE_DIR to $DESTINATION_DIR..."
cp -Rf "$SOURCE_DIR/"* "$DESTINATION_DIR"

# Change the current directory to the Metrolist application
METROLIST_DIR="../boston.gov-d8/docroot/modules/custom/bos_components/modules/bos_web_app/apps/metrolist"
echo "Changing directory to $METROLIST_DIR..."
cd "$METROLIST_DIR" || { echo "Failed to change directory to $METROLIST_DIR"; exit 1; }

# Remove the existing node_modules directory to ensure a fresh installation
echo "Removing existing node_modules directory..."
rm -rf node_modules

# Load environment variables from the .env file
echo "Loading environment variables from .env file..."
if [ -f .env ]; then
    source .env
else
    echo "Warning: .env file not found. Proceeding without loading environment variables."
fi

# Set the NVM directory for Node Version Manager
echo "Setting NVM directory..."
export NVM_DIR="$HOME/.nvm"

# Load NVM (Node Version Manager) to manage Node.js versions
echo "Loading NVM..."
if [ -f "$NVM_DIR/nvm.sh" ]; then
    source "$NVM_DIR/nvm.sh"
else
    echo "Error: NVM script not found. Please install NVM to manage Node.js versions."
    exit 1
fi

# Use Node.js version 14
echo "Using Node.js version 14..."
nvm use 14

npm install --verbose

install_libraries() {
  while true; do
    # Prompt the user to input a library name
    read -p "Enter the library name (or press Enter to finish): " lib_name

    # If the user presses Enter without typing anything
    if [ -z "$lib_name" ]; then
      read -p "Are you sure you want to proceed without adding any more libraries? [Y/n]: " confirmation
      # Proceed if the user confirms or presses Enter
      if [[ "$confirmation" == "Y" || "$confirmation" == "y" || -z "$confirmation" ]]; then
        break
      else
        continue
      fi
    fi

    # Check if the library exists on npm
    if npm view "$lib_name" &>/dev/null; then
      echo "Library '$lib_name' found. Installing..."
      npm install "$lib_name" --verbose
    else
      echo "Library '$lib_name' not found in npm. Please check the name and try again."
    fi
  done
}

# Start the library installation process
install_libraries

# Build the application
echo "Building the application..."
npm run build --verbose
echo "Build process completed successfully."