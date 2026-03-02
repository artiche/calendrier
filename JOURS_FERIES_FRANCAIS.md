# Jours Fériés Français - Règles de Calcul

## Vue d'ensemble
Le calendrier français compte 11 jours fériés. Certains sont fixes, d'autres dépendent du calendrier lunaire (Pâques).

---

## 1. **Jour de l'An**
- **Date** : 1er janvier
- **Type** : Fixe
- **Calcul** : Toujours le même jour quelque soit l'année
- **Formule** : `1er janvier`

---

## 2. **Lundi de Pâques**
- **Type** : Mouvant (dépend de Pâques)
- **Règle** : Le lundi suivant le dimanche de Pâques
- **Calcul de Pâques (Algorithme de Computus - Méthode de Gauss)** :
  - Pâques est le dimanche suivant la première Lune du Printemps
  - Pour calculer Pâques, on utilise l'Algorithme de Computus (Méthode de Gauss ou Meeus/Jones/Butcher pour plus de précision)
  
### Algorithme de Meeus/Jones/Butcher (recommandé, valide de 1583 à 4099) :
```
Soit A = année
a = A mod 19
b = A ÷ 100
c = A mod 100
d = b ÷ 4
e = b mod 4
f = (b + 8) ÷ 25
g = (b - f + 1) ÷ 3
h = (19a + b - d - g + 15) mod 30
i = c ÷ 4
k = c mod 4
l = (32 + 2e + 2i - h - k) mod 7
m = (a + 11h + 22l) ÷ 451
mois = (h + l - 7m + 114) ÷ 31  (1=mars, 2=avril)
jour = ((h + l - 7m + 114) mod 31) + 1

Pâques = jour du mois calculé
Lundi de Pâques = Pâques + 1 jour
```

---

## 3. **Fête du Travail**
- **Date** : 1er mai
- **Type** : Fixe
- **Calcul** : Toujours le même jour quelque soit l'année
- **Formule** : `1er mai`

---

## 4. **Victoire 1945**
- **Date** : 8 mai
- **Type** : Fixe
- **Calcul** : Toujours le même jour quelque soit l'année
- **Formule** : `8 mai`

---

## 5. **Ascension**
- **Date** : Dépend de Pâques
- **Type** : Mouvant
- **Règle** : 39 jours après le dimanche de Pâques (ou 38 jours après le Lundi de Pâques)
- **Formule** : 
  ```
  Ascension = Dimanche de Pâques + 39 jours
  ou
  Ascension = Lundi de Pâques + 38 jours
  ```

---

## 6. **Lundi de Pentecôte**
- **Date** : Dépend de Pâques
- **Type** : Mouvant
- **Règle** : 50 jours après le dimanche de Pâques (ou 49 jours après le Lundi de Pâques)
- **Formule** : 
  ```
  Lundi de Pentecôte = Dimanche de Pâques + 50 jours
  ou
  Lundi de Pentecôte = Lundi de Pâques + 49 jours
  ```

---

## 7. **Fête Nationale**
- **Date** : 14 juillet
- **Type** : Fixe
- **Calcul** : Toujours le même jour quelque soit l'année
- **Formule** : `14 juillet`

---

## 8. **Assomption**
- **Date** : 15 août
- **Type** : Fixe
- **Calcul** : Toujours le même jour quelque soit l'année
- **Formule** : `15 août`

---

## 9. **Armistice 1918**
- **Date** : 11 novembre
- **Type** : Fixe
- **Calcul** : Toujours le même jour quelque soit l'année
- **Formule** : `11 novembre`

---

## 10. **Noël**
- **Date** : 25 décembre
- **Type** : Fixe
- **Calcul** : Toujours le même jour quelque soit l'année
- **Formule** : `25 décembre`

---

## Résumé des Jours Fériés

| Numéro | Nom | Date | Type | Dépend de |
|--------|-----|------|------|-----------|
| 1 | Jour de l'An | 1er janvier | Fixe | - |
| 2 | Lundi de Pâques | Variable | Mouvant | Pâques |
| 3 | Fête du Travail | 1er mai | Fixe | - |
| 4 | Victoire 1945 | 8 mai | Fixe | - |
| 5 | Ascension | Variable | Mouvant | Pâques + 39j |
| 6 | Lundi de Pentecôte | Variable | Mouvant | Pâques + 50j |
| 7 | Fête Nationale | 14 juillet | Fixe | - |
| 8 | Assomption | 15 août | Fixe | - |
| 9 | Armistice 1918 | 11 novembre | Fixe | - |
| 10 | Noël | 25 décembre | Fixe | - |

**Total** : 10 jours fériés fixes + 1 jour variable (Lundi de Pâques = 11ème jour férié)

---

## Notes Importantes

### Jours Fériés Mouvants (3 au total)
Les trois jours fériés suivants changent chaque année :
1. **Lundi de Pâques** - Base de tous les calculs
2. **Ascension** - Pâques + 39 jours
3. **Lundi de Pentecôte** - Pâques + 50 jours

### Calcul de Pâques
Pâques est défini selon les règles du Concile de Nicée (325 AD) :
- **C'est le dimanche suivant la première Lune du Printemps** (plus précisément, la première Lune ecclésiastique du Printemps)
- La date minimale : 22 mars (exemple : 1818)
- La date maximale : 25 avril (exemple : 1943, 2038)
- Intervalle moyen : entre le 22 mars et le 25 avril

### Années Remarquables (2026)
Pour l'année 2026 :
- Pâques sera le 5 avril 2026
- Lundi de Pâques : 6 avril 2026
- Ascension : 14 mai 2026
- Lundi de Pentecôte : 25 mai 2026
