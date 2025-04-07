#!/bin/bash

source .env
psql -h $DB_HOST -p $DB_PORT -U $DB_USERNAME -d $DB_NAME -f install.sql 