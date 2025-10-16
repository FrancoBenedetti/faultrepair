-- SQL Query to Extract Regions by Province
-- Shows all regions grouped by province, including regions with no province assigned

SELECT
    CASE
        WHEN p.name IS NULL THEN 'NO PROVINCE ASSIGNED'
        ELSE CONCAT(p.name, ' Province')
    END as Province,
    COUNT(DISTINCT COALESCE(r.id, r2.id)) as Total_Regions,
    GROUP_CONCAT(DISTINCT CASE
        WHEN COALESCE(r.region_type, r2.region_type) = 'town'
        THEN COALESCE(r.name, r2.name)
        END ORDER BY COALESCE(r.name, r2.name) SEPARATOR ', '
    ) as Towns,
    GROUP_CONCAT(DISTINCT CASE
        WHEN COALESCE(r.region_type, r2.region_type) = 'district_municipality'
        THEN COALESCE(r.name, r2.name)
        END ORDER BY COALESCE(r.name, r2.name) SEPARATOR ', '
    ) as District_Municipalities,
    GROUP_CONCAT(DISTINCT CASE
        WHEN COALESCE(r.region_type, r2.region_type) = 'province'
        THEN COALESCE(r.name, r2.name)
        END ORDER BY COALESCE(r.name, r2.name) SEPARATOR ', '
    ) as Provinces_Listed
FROM regions p
LEFT JOIN region_hierarchy rh ON p.id = rh.ancestor_id AND p.region_type = 'province'
LEFT JOIN regions r ON rh.descendant_id = r.id
FULL OUTER JOIN (
    SELECT * FROM regions WHERE region_type IN ('town', 'district_municipality') AND id NOT IN (
        SELECT descendant_id FROM region_hierarchy rh2
        JOIN regions p2 ON rh2.ancestor_id = p2.id AND p2.region_type = 'province'
    )
) r2 ON 1=1 AND p.id IS NULL
WHERE (p.region_type = 'province' OR p.id IS NULL)
    AND (r.region_type IN ('town', 'district_municipality') OR r.id IS NULL)
    AND (r2.region_type IN ('town', 'district_municipality') OR r2.id IS NULL)
GROUP BY p.name, p.id
ORDER BY
    CASE WHEN p.name IS NULL THEN 'NO PROVINCE ASSIGNED' ELSE p.name END;

-- Alternative simpler query that works with MySQL (no FULL OUTER JOIN support)
SELECT
    COALESCE(p.name, 'NO PROVINCE ASSIGNED') as Province_Group,
    COUNT(DISTINCT CASE WHEN r.region_type IN ('town', 'district_municipality') THEN r.id END) as Region_Count,
    GROUP_CONCAT(DISTINCT CASE WHEN r.region_type = 'town' THEN r.name END ORDER BY r.name SEPARATOR ', ') as Towns,
    GROUP_CONCAT(DISTINCT CASE WHEN r.region_type = 'district_municipality' THEN r.name END ORDER BY r.name SEPARATOR ', ') as Districts,
    GROUP_CONCAT(DISTINCT CASE WHEN r.region_type = 'province' THEN r.name END ORDER BY r.name SEPARATOR ', ') as Province_Regions
FROM regions p
LEFT JOIN region_hierarchy rh ON p.id = rh.ancestor_id
LEFT JOIN regions r ON rh.descendant_id = r.id
WHERE p.region_type = 'province'
   OR p.id IS NULL
GROUP BY p.id, p.name
HAVING Province_Group <> 'NULL'

UNION ALL

-- Regions without province assignment
SELECT
    'NO PROVINCE ASSIGNED' as Province_Group,
    COUNT(*) as Region_Count,
    GROUP_CONCAT(DISTINCT CASE WHEN region_type = 'town' THEN name END ORDER BY name SEPARATOR ', ') as Towns,
    GROUP_CONCAT(DISTINCT CASE WHEN region_type = 'district_municipality' THEN name END ORDER BY name SEPARATOR ', ') as Districts,
    NULL as Province_Regions
FROM regions
WHERE region_type IN ('town', 'district_municipality')
  AND id NOT IN (
      SELECT descendant_id FROM region_hierarchy rh2
      JOIN regions p2 ON rh2.ancestor_id = p2.id
      WHERE p2.region_type = 'province'
  )
ORDER BY Province_Group;
