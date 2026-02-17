#!/bin/bash

# Fix operations files - Exit buttons should go to /index
sed -i 's|href="index"|href="/index"|g' operations/pick.php
sed -i 's|href="index"|href="/index"|g' operations/return.php

echo "Fixed operations files"
