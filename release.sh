start=`date +%s`

# Build the panel
yarn build:production

# Mkdir and cd to tmp
mkdir tmp
cd tmp/

# Clone the repo
git clone https://github.com/Nookure/NookTheme.git

cd NookTheme

# Remove the .git folder
rm -rf .git

# Copy the Compiled files
cp -r ../../public .

# Create the tar and zip files
tar -czvf ./NookTheme.tar.gz .
zip -r ./NookTheme.zip .

rm -rf ../../release/*

# Create releases folder if it doesn't exist
mkdir ../../release

# Move the files to releases
mv ./NookTheme.tar.gz ../../release/panel.tar.gz
mv ./NookTheme.zip ../../release/panel.zip

# Remove the tmp folder
cd ../../
rm -rf tmp/

end=`date +%s`

# Done
echo "Build in `expr $end - $start` seconds"