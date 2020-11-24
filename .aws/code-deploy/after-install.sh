#!/bin/bash

AWS_REGION="`curl -s http://169.254.169.254/latest/meta-data/placement/region`"

echo "AWS_REGION=${AWS_REGION}" >> /etc/environment
echo "PAYSTRING_ENVIRONMENT=production" >> /etc/environment

echo "export AWS_REGION=\"${AWS_REGION}\"" >> /etc/sysconfig/httpd
echo "export PAYSTRING_ENVIRONMENT=\"production\"" >> /etc/sysconfig/httpd