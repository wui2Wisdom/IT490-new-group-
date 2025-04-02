// Query Parameters: page, limit, search, genre
app.get('/movies', async (req, res) => {
  const { page = 1, limit = 10, search, genre } = req.query;
  const offset = (page - 1) * limit;

  // Build SQL query dynamically
  let query = 'SELECT * FROM movies';
  const params = [];
  
  if (search) {
    query += ' WHERE title ILIKE ?';
    params.push(`%${search}%`);
  }
  if (genre) {
    query += search ? ' AND genre = ?' : ' WHERE genre = ?';
    params.push(genre);
  }
  query += ' LIMIT ? OFFSET ?';
  params.push(parseInt(limit), offset);

  const movies = await db.query(query, params);
  
  // Include pagination metadata
  res.json({
    data: movies,
    pagination: {
      page: parseInt(page),
      limit: parseInt(limit),
      total: await getTotalCount('movies') // Helper function
    }
  });
});