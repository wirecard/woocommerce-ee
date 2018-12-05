#!/bin/bash

for filename in ./*.po; do
	msgfmt -o "$(basename "$filename" .po).mo" "$filename"
done
