<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

class CacheController extends Controller
{
    /**
     * Probar la conexión de Redis para caché
     */
    public function testConnection()
    {
        try {
            $testKey = 'cache_test_' . time();
            $testValue = 'Redis cache is working at ' . now();
            
            // Almacenar en caché por 60 segundos
            Cache::put($testKey, $testValue, 60);
            
            // Recuperar del caché
            $retrievedValue = Cache::get($testKey);
            
            if ($retrievedValue === $testValue) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Redis cache connection is working correctly',
                    'test_key' => $testKey,
                    'test_value' => $retrievedValue,
                    'timestamp' => now()
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Cache value mismatch'
            ], 500);
            
        } catch (Exception $e) {
            Log::error('Redis cache test failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Redis cache connection failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Ejemplo de caché de consulta de base de datos
     */
    public function getCachedUsers()
    {
        try {
            $users = Cache::remember('all_users', 600, function () {
                Log::info('Fetching users from database (cache miss)');
                return DB::table('users')->select('id', 'primer_nombre', 'segundo_nombre', 'primer_apellido', 'segundo_apellido', 'email', 'created_at')->get();
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $users,
                'cached' => Cache::has('all_users'),
                'cache_key' => 'all_users',
                'ttl_seconds' => 600
            ]);
            
        } catch (Exception $e) {
            Log::error('Error fetching cached users: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Almacenar datos en caché con diferentes TTL
     */
    public function storeInCache(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
            'ttl' => 'nullable|integer|min:1|max:86400' // Máximo 24 horas
        ]);
        
        try {
            $key = $request->input('key');
            $value = $request->input('value');
            $ttl = $request->input('ttl', 3600); // Por defecto 1 hora
            
            Cache::put($key, $value, $ttl);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data stored in cache successfully',
                'key' => $key,
                'ttl_seconds' => $ttl,
                'expires_at' => now()->addSeconds($ttl)
            ]);
            
        } catch (Exception $e) {
            Log::error('Error storing data in cache: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to store data in cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Recuperar datos del caché
     */
    public function getFromCache($key)
    {
        try {
            if (Cache::has($key)) {
                $value = Cache::get($key);
                
                return response()->json([
                    'status' => 'success',
                    'key' => $key,
                    'value' => $value,
                    'found' => true
                ]);
            }
            
            return response()->json([
                'status' => 'success',
                'key' => $key,
                'value' => null,
                'found' => false,
                'message' => 'Key not found in cache'
            ]);
            
        } catch (Exception $e) {
            Log::error('Error retrieving data from cache: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data from cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar una clave específica del caché
     */
    public function forgetCache($key)
    {
        try {
            $existed = Cache::has($key);
            Cache::forget($key);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Cache key removed successfully',
                'key' => $key,
                'existed' => $existed
            ]);
            
        } catch (Exception $e) {
            Log::error('Error removing cache key: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove cache key',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Limpiar todo el caché
     */
    public function flushCache()
    {
        try {
            Cache::flush();
            
            return response()->json([
                'status' => 'success',
                'message' => 'All cache cleared successfully',
                'timestamp' => now()
            ]);
            
        } catch (Exception $e) {
            Log::error('Error flushing cache: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to flush cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener estadísticas de Redis
     */
    public function getCacheStats()
    {
        try {
            $redis = Redis::connection('cache');
            
            $info = $redis->info();
            $memoryInfo = $redis->info('memory');
            $keyspaceInfo = $redis->info('keyspace');
            
            return response()->json([
                'status' => 'success',
                'redis_info' => [
                    'version' => $info['redis_version'] ?? 'unknown',
                    'uptime_seconds' => $info['uptime_in_seconds'] ?? 0,
                    'connected_clients' => $info['connected_clients'] ?? 0,
                    'total_commands_processed' => $info['total_commands_processed'] ?? 0
                ],
                'memory_info' => [
                    'used_memory' => $memoryInfo['used_memory_human'] ?? 'unknown',
                    'used_memory_peak' => $memoryInfo['used_memory_peak_human'] ?? 'unknown',
                    'memory_fragmentation_ratio' => $memoryInfo['mem_fragmentation_ratio'] ?? 0
                ],
                'keyspace_info' => $keyspaceInfo,
                'total_keys' => $redis->dbsize(),
                'timestamp' => now()
            ]);
            
        } catch (Exception $e) {
            Log::error('Error getting cache stats: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get cache statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Ejemplo de caché con tags (si está disponible)
     */
    public function cacheWithTags(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
            'tags' => 'required|array',
            'tags.*' => 'string|max:100',
            'ttl' => 'nullable|integer|min:1|max:86400'
        ]);
        
        try {
            $key = $request->input('key');
            $value = $request->input('value');
            $tags = $request->input('tags');
            $ttl = $request->input('ttl', 3600);
            
            Cache::tags($tags)->put($key, $value, $ttl);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data stored in cache with tags successfully',
                'key' => $key,
                'tags' => $tags,
                'ttl_seconds' => $ttl
            ]);
            
        } catch (Exception $e) {
            Log::error('Error storing data in cache with tags: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to store data in cache with tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Limpiar caché por tags
     */
    public function flushCacheByTags(Request $request)
    {
        $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string|max:100'
        ]);
        
        try {
            $tags = $request->input('tags');
            
            Cache::tags($tags)->flush();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Cache cleared for specified tags',
                'tags' => $tags,
                'timestamp' => now()
            ]);
            
        } catch (Exception $e) {
            Log::error('Error flushing cache by tags: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to flush cache by tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}