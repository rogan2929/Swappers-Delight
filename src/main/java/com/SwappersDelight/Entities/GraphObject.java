/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

package com.SwappersDelight.Entities;

import java.util.Objects;
import com.google.code.gson;

/**
 * Base class for all Facebook Graph API objects.
 * @author scalesr
 */
public abstract class GraphObject {
    protected String id;

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
        if (!Objects.equals(this.id, other.id)) {
            return false;
        }
        return true;
    }
    
    /**
     * Converts the Facebook GraphObject to a JSON-encoded string.
     * @return 
     */
    public String toJSONString() {
        //return "";
        Gson gson = new Gson();
    }
}
