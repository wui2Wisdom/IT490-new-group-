app.get('/movies/:id', async (req, res) => {
    const movieId = req.params.id;
    
    const movie = await db.query(
      `SELECT 
        m.*, 
        AVG(r.rating) AS avg_rating,
        COUNT(r.review_id) AS review_count
      FROM movies m
      LEFT JOIN reviews r ON m.movie_id = r.movie_id
      WHERE m.movie_id = ?
      GROUP BY m.movie_id`,
      [movieId]
    );
  
    if (!movie) return res.status(404).json({ error: 'Movie not found' });
    
    res.json({
      ...movie,
      genres: movie.genre.split(','), // Convert genre string to array
      avg_rating: parseFloat(movie.avg_rating) || 0
    });
  });