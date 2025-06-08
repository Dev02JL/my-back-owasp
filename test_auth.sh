#!/bin/bash

echo "1. Test de la route publique :"
curl -k -X GET https://localhost:8002/api/public \
  -H "Accept: application/json"

echo -e "\n\n2. Test de la route protégée (sans authentification) :"
curl -k -X GET https://localhost:8002/api/protected \
  -H "Accept: application/json"

echo -e "\n\n3. Authentification :"
TOKEN=$(curl -k -X POST https://localhost:8002/api/login_check \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"gus@ta.ve","password":"MotPasse123"}' \
  | grep -o '"token":"[^"]*' | cut -d'"' -f4)

echo "Token JWT obtenu : $TOKEN"

echo -e "\n\n4. Test de la route protégée (avec token JWT) :"
curl -k -X GET https://localhost:8002/api/protected \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

echo -e "\n\n5. Test de la route admin (avec token JWT) :"
curl -k -X GET https://localhost:8002/api/admin \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

# Nettoyage
rm cookies.txt 