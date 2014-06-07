typedef long int int64_t;
typedef long int int64;
#define NULL 0
#define S(n) (const char *)n

#define APC_BEGIN(archive)

#ifdef COMPRESS_PRIMING_DATA

#define APC_END(archive)                                                    \
  void apc_load_##archive() {                                               \
    HPHP::cache_info c;                                                     \
    c.a_name = #archive;                                                    \
    c.use_const = HPHP_ARCHIVE_CONST_FETCH;                                 \
    apc_load_impl_compressed(&c, int_lens, int_keys, (long long*)int_values,\
                             char_lens, char_keys, char_values,             \
                             string_lens, strings, object_lens, objects,    \
                             thrift_lens, thrifts, other_lens, others);     \
  }                                                                         \
  void const_load_##archive() {                                             \
    HPHP::cache_info c;                                                     \
    c.a_name = #archive;                                                    \
    c.use_const = HPHP_ARCHIVE_CONST_FETCH;                                 \
    const_load_impl_compressed(&c, int_lens, int_keys, (long long*)int_values, \
                               char_lens, char_keys, char_values,           \
                               string_lens, strings, object_lens, objects,  \
                               thrift_lens, thrifts, other_lens, others);   \
  }                                                                         \

#else // COMPRESS_PRIMING_DATA

#define APC_END(archive)                                          \
  void apc_load_##archive() {                                     \
    HPHP::cache_info c;                                           \
    c.a_name = #archive;                                          \
    c.use_const = HPHP_ARCHIVE_CONST_FETCH;                       \
    apc_load_impl(&c, int_keys, (long long*)int_values,           \
                  char_keys, char_values,                         \
                  strings, objects, thrifts, others);             \
  }                                                               \
  void const_load_##archive() {                                   \
    HPHP::cache_info c;                                           \
    c.a_name = #archive;                                          \
    c.use_const = HPHP_ARCHIVE_CONST_FETCH;                       \
    const_load_impl(&c, int_keys, (long long*)int_values,         \
                    char_keys, char_values,                       \
                    strings, objects, thrifts, others);           \
  }                                                               \

#endif // COMPRESS_PRIMING_DATA

static_assert(sizeof(int64_t) == sizeof(long long),
              "Must be able to cast an int64* to a long long*");

namespace HPHP {

// Structure to hold cache meta data
// Same definition in ext_apc.cpp
struct cache_info {
  char *a_name;
  bool use_const;
};

extern
void apc_load_impl_compressed
    (struct cache_info *info,
     int *int_lens, const char *int_keys, long long *int_values,
     int *char_lens, const char *char_keys, char *char_values,
     int *string_lens, const char *strings,
     int *object_lens, const char *objects,
     int *thrift_lens, const char *thrifts,
     int *other_lens, const char *others);

extern
void const_load_impl_compressed
    (struct cache_info *info,
     int *int_lens, const char *int_keys, long long *int_values,
     int *char_lens, const char *char_keys, char *char_values,
     int *string_lens, const char *strings,
     int *object_lens, const char *objects,
     int *thrift_lens, const char *thrifts,
     int *other_lens, const char *others);

// Note (qixin): the following would eventually be deprecated.

extern void apc_load_impl(struct cache_info *info,
                          const char **int_keys, long long *int_values,
                          const char **char_keys, char *char_values,
                          const char **strings, const char **objects,
                          const char **thrifts, const char **others);

extern void const_load_impl(struct cache_info *info,
                            const char **int_keys, long long *int_values,
                            const char **char_keys, char *char_values,
                            const char **strings, const char **objects,
                            const char **thrifts, const char **others);
}
