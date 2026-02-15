from django.http import JsonResponse
from django.db import connection

# Vulnerability: Blind SQL Injection or Error-based SQL Injection depending on usage
# Although Django ORM is safe, we use raw SQL here to introduce the vulnerability.

def search_users(request):
    query = request.GET.get('q', '')
    
    # VULNERABILITY: Direct string concatenation in SQL query
    sql = f"SELECT username FROM auth_user WHERE username LIKE '%{query}%'"
    
    try:
        with connection.cursor() as cursor:
            # We don't actually have 'auth_user' table populated since we skipped migrate, 
            # but we can create a dummy table in a migration or just assume it fails.
            # To make it realistic, let's just execute it. 
            # Even if table doesn't exist, the SQL injection is present.
            cursor.execute(sql)
            results = cursor.fetchall()
            
        return JsonResponse({'results': results})
    except Exception as e:
        return JsonResponse({'error': str(e)}, status=500)
