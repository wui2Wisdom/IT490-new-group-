app.post('/favorites', authMiddleware, async (req, res) => {
    const { movie_id } = req.body;
    const user_id = req.user.id;
  
    // Check if movie exists
    const movie = await db.query('SELECT * FROM movies WHERE movie_id = ?', [movie_id]);
    if (!movie) return res.status(404).json({ error: 'Movie not found' });
  
    // Avoid duplicates
    const existing = await db.query(
      'SELECT * FROM user_favorites WHERE user_id = ? AND movie_id = ?',
      [user_id, movie_id]
    );
    if (existing) return res.status(409).json({ error: 'Already favorited' });
  
    await db.query(
      'INSERT INTO user_favorites (user_id, movie_id) VALUES (?, ?)',
      [user_id, movie_id]
    );
  
    res.status(201).json({ success: true });
  });