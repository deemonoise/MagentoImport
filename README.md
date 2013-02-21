MagentoImport
=============

Only one small script to import products data quick. It generate direct mysql requests, but not execute them. 

http://shaurmalab.github.com/MagentoImport  Magento Import Products Fase MySQL 

Description 
===========

Do you have thousands of products in your Magento store? Tired of importing eav data?

This script will allow you to do all fast!

Put this file to your magento core folder.
Put import file near and name it "import.csv".
Be sure that you are using \t delimeter (or use options inside file)
Run file!
Watch for result.sql in Magento root.
Options are placed at the beginning of file: $importFile = 'import.csv'; $resultFile = 'result.sql'; $delimeter = "\t"; // can be a ","

Notices:

script don't create Products, Attributes or Attribute Options, but can import Attribute Values if they are not in database yet (or change existed values)
SQL queries generated for all stores in same time
not importing prices like '1,000.00', only '1000.00'
Tested on Magento 1.4.1.1
