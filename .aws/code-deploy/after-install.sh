#!/bin/bash

AWS_REGION="`curl -s http://169.254.169.254/latest/dynamic/instance-identity/document | jq .region -r`"

echo "AWS_REGION=${AWS_REGION}" >> /etc/environment
echo "PAYID_ENVIRONMENT=production" >> /etc/environment

echo "export AWS_REGION=\"${AWS_REGION}\"" >> /etc/sysconfig/httpd
echo "export PAYID_ENVIRONMENT=\"production\"" >> /etc/sysconfig/httpd