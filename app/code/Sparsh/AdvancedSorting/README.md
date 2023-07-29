##Sparsh Advanced Sorting Extension
Advanced Sorting extension allows customers to sort products by Best Sellers, Top Rated, New Arrivals, Most Viewed, and Reviews Count options on catalog listing page.

##Support: 
version - 2.3.x, 2.4.x

##How to install Extension

1. Download the archive file.
2. Unzip the files
3. Create a folder [Magento_Root]/app/code/Sparsh/AdvancedSorting
4. Drop/move the unzipped files to directory '[Magento_Root]/app/code/Sparsh/AdvancedSorting'

#Enable Extension:
- php bin/magento module:enable Sparsh_AdvancedSorting
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento setup:static-content:deploy
- php bin/magento cache:flush

#Disable Extension:
- php bin/magento module:disable Sparsh_AdvancedSorting
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento setup:static-content:deploy
- php bin/magento cache:flush
