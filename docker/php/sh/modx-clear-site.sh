#!/bin/bash

echo "Start cleaning site directory: ${ROOT_PATH} ..."
rm -rf "${ROOT_PATH:?}"/*
echo "Finish cleaning site directory"