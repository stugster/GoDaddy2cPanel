# GoDaddy2cPanel
PHP tool to pull DNS host records from GoDaddy API and push into cPanel DNS via WHM API

We developed this tool to assist in the migration of domain nameserver records from GoDaddy DNS to cPanel. Prior to moving registar, we wanted to make sure we controlled DNS. This tool will pull from "domains.txt" each domain you want to migrate. It'll then probe GoDaddy's API for the DNS host records. Once it has them, it'll then interface with your WHM cPanel box and create the DNS zone accounts there.

Note: it doesn't create cPanel user/webhosting accounts. It will only create the DNS Zones!

Questions? support@considerit.co.uk
