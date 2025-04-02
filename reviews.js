app.post('/reviews', authMiddleware, async (req, res) => {
    const { movie_id, rating, review_text } = req.body;
    const user_id = req.user.id;
  
    // Validate input
    if (!movie_id || rating < 1 || rating > 5) {
      return res.status(400).json({ error: 'Invalid input' });
    }
  
    // Save review to database
    const review = await db.query(
      `INSERT INTO reviews (user_id, movie_id, rating, review_text)
       VALUES (?, ?, ?, ?) RETURNING *`,
      [user_id, movie_id, rating, review_text]
    );
  
    // Publish to RabbitMQ for async tasks (e.g., email notifications)
    rabbitmq.publish('reviews', JSON.stringify({
      type: 'NEW_REVIEW',
      review_id: review.review_id,
      user_id,
      movie_id
    }));
  
    res.status(201).json(review);
  });