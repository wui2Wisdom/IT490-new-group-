app.get('/favorites', authMiddleware, async (req, res) => {
    const user_id = req.user.id;
    
    const favorites = await db.query(
      `SELECT m.* 
       FROM user_favorites uf
       JOIN movies m ON uf.movie_id = m.movie_id
       WHERE uf.user_id = ?`,
      [user_id]
    );
  
    res.json(favorites);
  });