#!/bin/bash

echo "1. Test d'inscription avec des données valides :"
curl -k -X POST https://localhost:8002/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com","password":"MotPasse123"}'

echo -e "\n\n2. Test d'inscription avec email manquant :"
curl -k -X POST https://localhost:8002/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"password":"MotPasse123"}'

echo -e "\n\n3. Test d'inscription avec mot de passe manquant :"
curl -k -X POST https://localhost:8002/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com"}'

echo -e "\n\n4. Test d'inscription avec email invalide :"
curl -k -X POST https://localhost:8002/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"email-invalide","password":"MotPasse123"}'

echo -e "\n\n5. Test d'inscription avec mot de passe trop court :"
curl -k -X POST https://localhost:8002/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com","password":"123"}' 