FROM memcached:1.6.42-alpine

CMD ["memcached", "-m", "512", "-o", "slab_reassign,slab_automove=2"]
