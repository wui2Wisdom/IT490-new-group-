app.get('/reviews', async (req, res) => {
    const { movie_id, page = 1, limit = 10, min_rating } = req.query;
    const offset = (page - 1) * limit;
  
    let query = `
      SELECT r.*, u.username 
      FROM reviews r
      JOIN users u ON r.user_id = u.id
      WHERE r.movie_id = ?
    `;
    const params = [movie_id];
  
    if (min_rating) {
      query += ' AND r.rating >= ?';
      params.push(min_rating);
    }
  
    query += ' LIMIT ? OFFSET ?';
    params.push(parseInt(limit), offset);
  
    const reviews = await db.query(query, params);
    res.json(reviews);
  });