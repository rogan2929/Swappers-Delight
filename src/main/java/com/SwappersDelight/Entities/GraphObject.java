/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package com.SwappersDelight.Entities;

import com.google.gson.Gson;
import java.util.Date;
import java.util.Objects;

/**
 * Base class for all Facebook Graph API objects.
 *
 * @author scalesr
 */
public abstract class GraphObject {

    protected String id;
    protected Date createdTime;
    protected Date updatedTime;
    
    public GraphObject() {
        
    }

    public GraphObject(String id) {
        this.id = id;
    }

    public Date getCreatedTime() {
        return createdTime;
    }

    public void setCreatedTime(Date createdTime) {
        this.createdTime = createdTime;
    }

    public Date getUpdatedTime() {
        return updatedTime;
    }

    public void setUpdatedTime(Date updatedTime) {
        this.updatedTime = updatedTime;
    }

    public String getId() {
        return id;
    }

    public void setId(String id) {
        this.id = id;
    }

    @Override
    public int hashCode() {
        int hash = 3;
        hash = 41 * hash + Objects.hashCode(this.id);
        return hash;
    }

    @Override
    public boolean equals(Object obj) {
        if (obj == null) {
            return false;
        }
        if (getClass() != obj.getClass()) {
            return false;
        }
        final GraphObject other = (GraphObject) obj;

        return Objects.equals(this.id, other.id);
    }

    /**
     * Converts the Facebook GraphObject to a JSON-encoded string.
     *
     * @return
     */
    public String toJSONString() {
        return (new Gson()).toJson(this);
    }
}
